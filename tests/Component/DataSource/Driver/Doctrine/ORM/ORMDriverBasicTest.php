<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Doctrine\ORM\ORMDriver;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Event\PostGetResult;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Exception\DoctrineDriverException;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Boolean;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Date;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\DateTime;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\AbstractFieldType;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\FieldTypeInterface;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Entity;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Number;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Text;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Time;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\FieldTypeInterface as CoreFieldTypeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Tests\FSi\Component\DataSource\Fixtures\Entity\Category;
use Tests\FSi\Component\DataSource\Fixtures\Entity\Group;
use Tests\FSi\Component\DataSource\Fixtures\Entity\News;

final class ORMDriverBasicTest extends TestCase
{
    /**
     * @return array<array<string>>
     */
    public static function fieldNameProvider(): array
    {
        return [
            ['text'],
            ['number'],
            ['entity'],
            ['date'],
            ['time'],
            ['datetime'],
            ['boolean'],
        ];
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCreation(): void
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        $qb->method('getRootAliases')->willReturn(['e']);
        new ORMDriver(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EventDispatcherInterface::class),
            [],
            $qb,
            null
        );
    }

    public function testBasicGetResult(): void
    {
        $fields = [];

        for ($x = 0; $x < 6; $x++) {
            $fieldType = $this->createMock(AbstractFieldType::class);
            $fieldType->expects(self::once())->method('buildQuery');
            $field = $this->createMock(FieldInterface::class);
            $field->method('getType')->willReturn($fieldType);

            $fields[] = $field;
        }

        $em = $this->createEntityManager();

        $qb = $em->createQueryBuilder()->select('n')->from(News::class, 'n');

        $driver = new ORMDriver(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EventDispatcherInterface::class),
            [],
            $qb,
            null
        );
        $driver->getResult($fields, 0, 20);
    }

    public function testResultWhenFieldIsNotAnORMField(): void
    {
        $fieldType = $this->createMock(CoreFieldTypeInterface::class);
        $field = $this->createMock(FieldInterface::class);
        $field->method('getType')->willReturn($fieldType);

        $em = $this->getEntityManagerMock();
        $qb = $this->getMockBuilder(QueryBuilder::class)->setConstructorArgs([$em])->getMock();
        $qb->method('select')->willReturn($qb);
        $qb->method('getRootAliases')->willReturn(['e']);

        $em->method('createQueryBuilder')->willReturn($qb);

        $driver = new ORMDriver(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EventDispatcherInterface::class),
            [],
            $qb,
            null
        );
        $this->expectException(DoctrineDriverException::class);
        $driver->getResult([$field], 0, 20);
    }

    /**
     * @dataProvider fieldNameProvider
     */
    public function testCoreFields(string $type): void
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        $this->extendWithRootEntities($em, $qb);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManager')->willReturn($em);
        $managerRegistry->method('getManagerForClass')->willReturn($em);

        $dataSource = $this->createMock(DataSourceInterface::class);

        $driver = new ORMDriver(
            $managerRegistry,
            $this->createMock(EventDispatcherInterface::class),
            [
                new Boolean([]),
                new Date([]),
                new DateTime([]),
                new Entity([]),
                new Number([]),
                new Text([]),
                new Time([]),
            ],
            $qb,
            null
        );
        self::assertTrue($driver->hasFieldType($type));
        $fieldType = $driver->getFieldType($type);
        self::assertInstanceOf(FieldTypeInterface::class, $fieldType);

        $field = $fieldType->createField($dataSource, 'test', ['comparison' => 'eq']);

        self::assertEquals($field->getOption('field'), $field->getName());

        $this->expectException(InvalidOptionsException::class);
        $fieldType = $driver->getFieldType($type);
        $fieldType->createField($dataSource, 'test', ['comparison' => 'wrong']);
    }

    public function testExtensionsCalls(): void
    {
        $em = $this->createEntityManager();
        $qb = $em->createQueryBuilder()->select('n')->from(News::class, 'n');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $driver = new ORMDriver(
            $this->createMock(ManagerRegistry::class),
            $eventDispatcher,
            [],
            $qb,
            null
        );

        $eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive([self::isInstanceOf(PreGetResult::class)], [self::isInstanceOf(PostGetResult::class)]);
        $driver->getResult([], 0, 20);
    }

    /**
     * @return MockObject&EntityManagerInterface
     */
    private function getEntityManagerMock(): MockObject
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @param MockObject&EntityManagerInterface $em
     * @return MockObject&QueryBuilder
     */
    private function getQueryBuilderMock(EntityManagerInterface $em): MockObject
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)->setConstructorArgs([$em])->getMock();
        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('getRootAliases')->willReturn(['e']);

        $em->method('createQueryBuilder')->willReturn($qb);

        return $qb;
    }

    /**
     * @param MockObject&EntityManagerInterface $em
     * @param MockObject&QueryBuilder $qb
     * @param array<int,array{string,bool}> $map
     */
    private function extendWithRootEntities(
        EntityManagerInterface $em,
        QueryBuilder $qb,
        array $map = [['entity', true]]
    ): void {
        $returnMap = [];
        foreach ($map as $info) {
            /** @var MockObject|ClassMetadata<object> $metadata */
            $metadata = $this->createMock(ClassMetadata::class);
            $metadata->isIdentifierComposite = $info[1];

            $returnMap[] = [$info[0], $metadata];
        }

        $qb->method('getRootEntities')->willReturn(['entity']);
        $qb->method('getEntityManager')->willReturn($em);

        $em->method('getClassMetadata')->willReturnMap($returnMap);
    }

    private function createEntityManager(): EntityManager
    {
        $config = Setup::createConfiguration(true, null, null);
        $config->setMetadataDriverImpl(
            new XmlDriver(
                new SymfonyFileLocator(
                    [__DIR__ . '/../../../Fixtures/doctrine' => 'Tests\FSi\Component\DataSource\Fixtures\Entity'],
                    '.orm.xml'
                )
            )
        );
        $em = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $tool = new SchemaTool($em);
        $tool->createSchema([
            $em->getClassMetadata(News::class),
            $em->getClassMetadata(Category::class),
            $em->getClassMetadata(Group::class),
        ]);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManager')->willReturn($em);
        $managerRegistry->method('getManagerForClass')->willReturn($em);

        return $em;
    }
}
