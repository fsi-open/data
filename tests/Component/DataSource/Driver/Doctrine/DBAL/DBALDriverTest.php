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
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFieldInterface;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Event\PostGetResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field\Boolean;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field\Date;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field\DateTime;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field\Number;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field\Text;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field\Time;
use FSi\Component\DataSource\Field\FieldInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class DBALDriverTest extends TestBase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->getMemoryConnection();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCreation(): void
    {
        $qb = $this->connection->createQueryBuilder();

        new DBALDriver($this->createMock(EventDispatcherInterface::class), [], $qb, 'e');
    }

    /**
     * Checks exception when fields aren't proper instances.
     */
    public function testGetResultExceptionWhenFieldIsNotDBALField(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver($this->createMock(EventDispatcherInterface::class), [], $qb, 'e');

        $this->expectException(DBALDriverException::class);
        $fields = [$this->createMock(FieldInterface::class)];
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks basic getResult call.
     */
    public function testAllFieldsBuildQueryMethod(): void
    {
        $fields = [];

        for ($x = 0; $x < 6; $x++) {
            $fieldType = $this->createMock(DBALAbstractField::class);
            $fieldType->expects(self::once())->method('buildQuery');
            $field = $this->createMock(FieldInterface::class);
            $field->method('getType')->willReturn($fieldType);

            $fields[] = $field;
        }

        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver($this->createMock(EventDispatcherInterface::class), [], $qb, 'e');
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks extensions calls.
     */
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
     * Provides names of fields.
     *
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
     * Checks all fields of CoreExtension.
     *
     * @dataProvider fieldNameProvider
     */
    public function testCoreFields(string $type): void
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
        self::assertInstanceOf(DBALFieldInterface::class, $fieldType);

        $field = $fieldType->createField($this->createMock(DataSourceInterface::class), 'test', ['comparison' => 'eq']);
        self::assertEquals($field->getOption('field'), $field->getName());

        $this->expectException(InvalidOptionsException::class);
        $fieldType->createField($this->createMock(DataSourceInterface::class), 'test', ['comparison' => 'X']);
    }
}
