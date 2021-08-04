<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Doctrine\DBAL;

use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALResult;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class DBALFactoryTest extends TestBase
{
    private DriverFactoryInterface $factory;

    protected function setUp(): void
    {
        $this->loadTestData($this->getMemoryConnection());
        $this->factory = $this->getDriverFactory();
    }

    public function testDriverType(): void
    {
        self::assertEquals('doctrine-dbal', $this->factory::getDriverType());
    }

    public function testExceptionWhenNoTableAndBuilder(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->createDriver([]);
    }

    public function testTableOption(): void
    {
        $driver = $this->factory->createDriver(['table' => 'table_name']);
        self::assertInstanceOf(DBALDriver::class, $driver);
    }

    public function testQueryBuilderOption(): void
    {
        $qb = $this->getMemoryConnection()->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_CATEGORY_NAME, 'c');

        $driver = $this->factory->createDriver(['qb' => $qb]);
        self::assertInstanceOf(DBALDriver::class, $driver);
    }

    public function testInvalidConnection(): void
    {
        $driver = $this->factory->createDriver([
            'table' => 'table_name',
            'connection' => 'test',
        ]);
        self::assertInstanceOf(DBALDriver::class, $driver);
    }

    public function testPassIndexField(): void
    {
        $driver = $this->factory->createDriver([
            'table' => self::TABLE_CATEGORY_NAME,
            'indexField' => '[name]',
        ]);
        self::assertInstanceOf(DBALDriver::class, $driver);

        $result = $driver->getResult([], 0, 1);
        $iterator = $result->getIterator();
        self::assertInstanceOf(ArrayIterator::class, $iterator);
        self::assertEquals('name-1', $iterator->key());

        $driver = $this->factory->createDriver([
            'table' => self::TABLE_CATEGORY_NAME,
            'indexField' => static fn(array $row): string => $row['name'],
        ]);
        $result = $driver->getResult([], 0, 1);
        $iterator = $result->getIterator();
        self::assertInstanceOf(ArrayIterator::class, $iterator);
        self::assertEquals('name-1', $iterator->key());
    }
}
