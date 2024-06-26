<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataIndexer;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use FSi\Component\DataIndexer\DataIndexerInterface;
use FSi\Component\DataIndexer\DoctrineDataIndexer;
use FSi\Component\DataIndexer\Exception\InvalidArgumentException;
use FSi\Component\DataIndexer\Exception\RuntimeException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\Bike;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\Car;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\DeciduousTree;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\Monocycle;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\News;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\Oak;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\Plant;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\Post;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\Tree;
use Tests\FSi\Component\DataIndexer\Fixtures\Entity\Vehicle;

use function sys_get_temp_dir;

final class DoctrineDataIndexerTest extends TestCase
{
    private EntityManager $manager;

    public function testDataIndexerWithInvalidClass(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        new DoctrineDataIndexer($managerRegistry, DataIndexerInterface::class);
    }

    public function testGetIndexWithSimpleKey(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), News::class);

        self::assertSame($dataIndexer->getIndex(new News('foo')), 'foo');
    }

    public function testGetIndexWithCompositeKey(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Post::class);

        $news = new Post('foo', 'bar');

        self::assertSame($dataIndexer->getIndex($news), "foo|bar");
    }

    public function testGetDataWithSimpleKey(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), News::class);

        $news = new News('foo');
        $this->manager->persist($news);
        $this->manager->flush();
        $this->manager->clear();

        $news = $dataIndexer->getData('foo');
        self::assertInstanceOf(News::class, $news);

        self::assertSame($news->getId(), 'foo');
    }

    public function testGetDataWithCompositeKey(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Post::class);

        $post = new Post('foo', 'bar');
        $this->manager->persist($post);
        $this->manager->flush();
        $this->manager->clear();

        $post = $dataIndexer->getData("foo|bar");
        self::assertInstanceOf(Post::class, $post);

        self::assertSame($post->getIdFirstPart(), 'foo');
        self::assertSame($post->getIdSecondPart(), 'bar');
    }

    public function testGetDataWithCompositeKeyAndSeparatorInID(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Post::class);

        $this->expectException(RuntimeException::class);
        $dataIndexer->getData("foo||bar");
    }

    public function testGetDataSliceWithSimpleKey(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), News::class);

        $news1 = new News('foo');
        $news2 = new News('bar');
        $this->manager->persist($news1);
        $this->manager->persist($news2);
        $this->manager->flush();
        $this->manager->clear();

        /** @var array<int,News> $news */
        $news = $dataIndexer->getDataSlice(['foo', 'bar']);

        self::assertSame([$news[0]->getId(), $news[1]->getId()], ['bar', 'foo']);
    }

    public function testGetDataSliceWithCompositeKey(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Post::class);

        $post1 = new Post('foo', 'foo1');
        $post2 = new Post('bar', 'bar1');
        $this->manager->persist($post1);
        $this->manager->persist($post2);
        $this->manager->flush();
        $this->manager->clear();

        /** @var array<int,Post> $news */
        $news = $dataIndexer->getDataSlice(["foo|foo1", "bar|bar1"]);

        self::assertSame([
            $news[0]->getIdFirstPart() . '|' . $news[0]->getIdSecondPart(),
            $news[1]->getIdFirstPart() . '|' . $news[1]->getIdSecondPart(),
        ], ["bar|bar1", "foo|foo1"]);
    }

    public function testGetIndexWithSubclass(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Vehicle::class);

        // Creating subclasses of News
        $car = new Car('foo');
        $bike = new Bike('bar');

        self::assertSame($dataIndexer->getIndex($car), 'foo');
        self::assertSame($dataIndexer->getIndex($bike), 'bar');
        self::assertSame(Vehicle::class, $dataIndexer->getClass());
    }

    /**
     * For simple entity indexer must be set that class.
     */
    public function testCreateWithSimpleEntity(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), News::class);
        self::assertSame(News::class, $dataIndexer->getClass());
    }

    /**
     * For entity that extends other entity, indexer must set its parent.
     */
    public function testCreateWithSubclass(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Car::class);
        self::assertSame(Vehicle::class, $dataIndexer->getClass());
    }

    /**
     * For few levels of inheritance indexer must set its highest parent.
     */
    public function testCreateWithSubclasses(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Monocycle::class);
        self::assertSame(Vehicle::class, $dataIndexer->getClass());
    }

    /**
     * For entity that is on top of inheritance tree indexer must set given class.
     */
    public function testCreateWithEntityThatOtherInheritsFrom(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Vehicle::class);
        self::assertSame(Vehicle::class, $dataIndexer->getClass());
    }

    public function testCreateWithMappedSuperClass(): void
    {
        $this->expectException(RuntimeException::class);
        new DoctrineDataIndexer($this->getManagerRegistry(), Plant::class);
    }

    /**
     * For entity that inherits from mapped super class indexer must be set to
     * the same class that was created.
     */
    public function testCreateWithEntityThatInheritsFromMappedSuperClass(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Tree::class);
        self::assertSame(Tree::class, $dataIndexer->getClass());
    }

    public function testSecondLevelOfInheritanceFromMappedSuperClass(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), DeciduousTree::class);
        self::assertSame(Tree::class, $dataIndexer->getClass());
    }

    public function testThirdLevelOfInheritanceFromMappedSuperClass(): void
    {
        $dataIndexer = new DoctrineDataIndexer($this->getManagerRegistry(), Oak::class);
        self::assertSame(Tree::class, $dataIndexer->getClass());
    }

    protected function setUp(): void
    {
        $config = $this->getMockEntityManagerConfiguration();
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true,], $config);

        $manager = new EntityManager($connection, $config);
        $schema = array_map(
            fn(string $class) => $manager->getClassMetadata($class),
            [News::class, Post::class]
        );

        $schemaTool = new SchemaTool($manager);
        $schemaTool->dropSchema($schema);
        $schemaTool->updateSchema($schema);

        $this->manager = $manager;
    }

    /**
     * @return ManagerRegistry&MockObject
     */
    private function getManagerRegistry(): MockObject
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($this->manager);

        return $managerRegistry;
    }

    /**
     * @return Configuration&MockObject
     */
    private function getMockEntityManagerConfiguration(): MockObject
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getProxyDir')->willReturn(sys_get_temp_dir());

        $config->method('getNamingStrategy')->willReturn(new DefaultNamingStrategy());
        $config->expects(self::once())->method('getProxyNamespace')->willReturn('Proxy');
        $config->expects(self::once())->method('getAutoGenerateProxyClasses')
            ->willReturn(ProxyFactory::AUTOGENERATE_ALWAYS);
        $config->expects(self::once())->method('getClassMetadataFactoryName')->willReturn(ClassMetadataFactory::class);
        $config->method('getQuoteStrategy')->willReturn(new DefaultQuoteStrategy());

        $config->method('getMetadataDriverImpl')->willReturn(
            new XmlDriver(
                new SymfonyFileLocator(
                    [__DIR__ . '/Fixtures/doctrine' => 'Tests\FSi\Component\DataIndexer\Fixtures\Entity'],
                    '.orm.xml'
                )
            )
        );
        $config->method('getDefaultRepositoryClassName')->willReturn(EntityRepository::class);
        $config->method('getRepositoryFactory')->willReturn(new DefaultRepositoryFactory());

        return $config;
    }
}
