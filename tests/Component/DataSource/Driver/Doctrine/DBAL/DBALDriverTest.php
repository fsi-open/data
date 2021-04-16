<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Tests\Component\DataSource\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFieldInterface;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Exception\FieldException;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Tests\Component\DataSource\Driver\Doctrine\DBAL\Fixtures\DBALDriverExtension;
use FSi\Tests\Component\DataSource\Fixtures\FieldExtension;
use stdClass;

class DBALDriverTest extends TestBase
{
    /**
     * @var Connection
     */
    private $connection;

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

        new DBALDriver([], $qb, 'e');
    }

    /**
     * Checks creation exception.
     */
    public function testCreationExceptionWhenExtensionIsInvalid(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $this->expectException(DataSourceException::class);
        new DBALDriver([new stdClass()], $qb, 'e');
    }

    /**
     * Checks exception when fields aren't proper instances.
     */
    public function testGetResultExceptionWhenFieldIsNotDBALField(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver([], $qb, 'e');
        $this->expectException(DBALDriverException::class);

        $fields = [$this->createMock(FieldTypeInterface::class)];
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks basic getResult call.
     */
    public function testAllFieldsBuildQueryMethod(): void
    {
        $fields = [];

        for ($x = 0; $x < 6; $x++) {
            $field = $this->createMock(DBALAbstractField::class);
            $field->expects(self::once())->method('buildQuery');

            $fields[] = $field;
        }

        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver([], $qb, 'e');
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks exception when trying to access the query builder not during getResult method.
     */
    public function testGetQueryExceptionWhenNotInsideGetResult(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver([], $qb, 'e');
        $this->expectException(DBALDriverException::class);
        $driver->getQueryBuilder();
    }

    /**
     * Checks CoreExtension.
     */
    public function testCoreExtension(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $driver = new DBALDriver([new CoreExtension()], $qb, 'e');

        self::assertTrue($driver->hasFieldType('text'));
        self::assertTrue($driver->hasFieldType('number'));
        self::assertTrue($driver->hasFieldType('date'));
        self::assertTrue($driver->hasFieldType('time'));
        self::assertTrue($driver->hasFieldType('datetime'));
        self::assertTrue($driver->hasFieldType('boolean'));
        self::assertFalse($driver->hasFieldType('wrong'));

        $this->expectException(DataSourceException::class);
        $driver->getFieldType('wrong');
    }

    /**
     * Checks extensions calls.
     */
    public function testExtensionsCalls(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $extension = new DBALDriverExtension();
        $driver = new DBALDriver([], $qb, 'e');
        $driver->addExtension($extension);

        $driver->getResult([], 0, 20);
        self::assertEquals(['preGetResult', 'postGetResult'], $extension->getCalls());
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
        $driver = new DBALDriver([new CoreExtension()], $qb, 'e');
        self::assertTrue($driver->hasFieldType($type));
        $field = $driver->getFieldType($type);
        self::assertInstanceOf(DBALFieldInterface::class, $field);

        self::assertTrue($field->getOptionsResolver()->isDefined('field'));

        $comparisons = $field->getAvailableComparisons();
        self::assertGreaterThan(0, count($comparisons));

        foreach ($comparisons as $cmp) {
            $field = $driver->getFieldType($type);
            $field->setName('name');
            $field->setComparison($cmp);
            $field->setOptions([]);
        }

        self::assertEquals($field->getOption('field'), $field->getName());

        $this->expectException(FieldException::class);
        $field = $driver->getFieldType($type);
        $field->setComparison('wrong');
    }

    /**
     * Checks fields extensions calls.
     */
    public function testFieldsExtensionsCalls(): void
    {
        $extension = new FieldExtension();
        $parameter = [];

        $fields = [
            new Field\Text(),
            new Field\Number(),
            new Field\Date(),
            new Field\Time(),
            new Field\DateTime(),
        ];

        foreach ($fields as $field) {
            $field->addExtension($extension);

            $field->bindParameter([]);
            self::assertEquals(['preBindParameter', 'postBindParameter'], $extension->getCalls());
            $extension->resetCalls();

            $field->getParameter($parameter);
            self::assertEquals(['postGetParameter'], $extension->getCalls());
            $extension->resetCalls();

            $field->createView();
            self::assertEquals(['postBuildView'], $extension->getCalls());
            $extension->resetCalls();
        }
    }
}
