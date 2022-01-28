<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Event\PostGetResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType\AbstractFieldType;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType\Boolean;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType\Date;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType\DateTime;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType\FieldTypeInterface;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType\Number;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType\Text;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType\Time;
use FSi\Component\DataSource\Field\FieldInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

final class DBALDriverTest extends TestBase
{
    private Connection $connection;

    /**
     * @doesNotPerformAssertions
     */
    public function testCreation(): void
    {
        $qb = $this->connection->createQueryBuilder();

        new DBALDriver($this->createMock(EventDispatcherInterface::class), [], $qb, 'e');
    }

    public function testGetResultExceptionWhenFieldIsNotDBALField(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver($this->createMock(EventDispatcherInterface::class), [], $qb, 'e');

        $this->expectException(DBALDriverException::class);
        $fields = [$this->createMock(FieldInterface::class)];
        $driver->getResult($fields, 0, 20);
    }

    public function testAllFieldsBuildQueryMethod(): void
    {
        $fields = [];

        for ($x = 0; $x < 6; $x++) {
            $fieldType = $this->createMock(AbstractFieldType::class);
            $fieldType->expects(self::once())->method('buildQuery');
            $field = $this->createMock(FieldInterface::class);
            $field->method('getType')->willReturn($fieldType);

            $fields[] = $field;
        }

        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver($this->createMock(EventDispatcherInterface::class), [], $qb, 'e');
        $driver->getResult($fields, 0, 20);
    }

    public function testExtensionsCalls(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $driver = new DBALDriver($eventDispatcher, [], $qb, 'e');

        $eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive([self::isInstanceOf(PreGetResult::class)], [self::isInstanceOf(PostGetResult::class)]);
        $driver->getResult([], 0, 20);
    }

    /**
     * @return array<array<string>>
     */
    public static function fieldNameProvider(): array
    {
        return [
            ['text'],
            ['number'],
            ['date'],
            ['time'],
            ['datetime'],
            ['boolean'],
        ];
    }

    /**
     * @dataProvider fieldNameProvider
     */
    public function testAllCoreFields(string $type): void
    {
        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver(
            $this->createMock(EventDispatcherInterface::class),
            [
                new Boolean([]),
                new Date([]),
                new DateTime([]),
                new Number([]),
                new Text([]),
                new Time([]),
            ],
            $qb,
            'e'
        );
        self::assertTrue($driver->hasFieldType($type));
        $fieldType = $driver->getFieldType($type);
        self::assertInstanceOf(FieldTypeInterface::class, $fieldType);

        $field = $fieldType->createField($this->createMock(DataSourceInterface::class), 'test', ['comparison' => 'eq']);
        self::assertEquals($field->getOption('field'), $field->getName());

        $this->expectException(InvalidOptionsException::class);
        $fieldType->createField($this->createMock(DataSourceInterface::class), 'test', ['comparison' => 'X']);
    }

    protected function setUp(): void
    {
        $this->connection = $this->getMemoryConnection();
    }
}
