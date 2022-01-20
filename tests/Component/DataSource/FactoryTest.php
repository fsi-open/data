<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource;

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\Text;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use PHPUnit\Framework\TestCase;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\Exception\DataSourceException;
use Psr\EventDispatcher\EventDispatcherInterface;

class FactoryTest extends TestCase
{
    /**
     * Checks exception thrown when creating DataSource with non-existing driver
     */
    public function testFactoryException6(): void
    {
        $this->expectException(DataSourceException::class);
        $this->expectExceptionMessage('Driver "unknownDriver" doesn\'t exist.');

        $factory = new DataSourceFactory(
            $this->createMock(EventDispatcherInterface::class),
            new DriverFactoryManager([])
        );
        $factory->createDataSource('unknownDriver');
    }

    /**
     * Checks exception thrown when creating DataSource with non unique name.
     */
    public function testFactoryCreateDataSourceException1(): void
    {
        $this->expectException(DataSourceException::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory($eventDispatcher, [])]);
        $factory = new DataSourceFactory($eventDispatcher, $driveFactoryManager);

        $factory->createDataSource('collection', ['collection' => []], 'unique');
        $factory->createDataSource('collection', ['collection' => []], 'nonunique');
        $factory->createDataSource('collection', ['collection' => []], 'nonunique');
    }

    /**
     * Checks exception thrown when creating DataSource with wrong name.
     */
    public function testFactoryCreateDataSourceException2(): void
    {
        $this->expectException(DataSourceException::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory($eventDispatcher, [])]);
        $factory = new DataSourceFactory($eventDispatcher, $driveFactoryManager);

        $factory->createDataSource('collection', ['collection' => []], 'wrong-one');
    }

    /**
     * Checks exception thrown when creating DataSource with empty name.
     */
    public function testFactoryCreateDataSourceException3(): void
    {
        $this->expectException(DataSourceException::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory($eventDispatcher, [])]);
        $factory = new DataSourceFactory($eventDispatcher, $driveFactoryManager);

        $factory->createDataSource('collection', ['collection' => []], '');
    }

    /**
     * Checks fetching parameters of all and others datasources.
     */
    public function testGetAllAndOtherParameters(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory($eventDispatcher, [new Text([])])]);
        $factory = new DataSourceFactory($eventDispatcher, $driveFactoryManager);

        $params1 = [
            'datasource1' => [
                'fields' => [
                    'test' => 'a'
                ]
            ]
        ];

        $params2 = [
            'datasource2' => [
                'fields' => [
                    'test' => 'b'
                ]
            ]
        ];

        $result = array_merge($params1, $params2);

        $dataSource1 = $factory->createDataSource('collection', [], 'datasource1');
        $dataSource1->addField('test', 'text', ['comparison' => 'eq']);
        $dataSource1->bindParameters($params1);
        $dataSource2 = $factory->createDataSource('collection', [], 'datasource2');
        $dataSource2->addField('test', 'text', ['comparison' => 'eq']);
        $dataSource2->bindParameters($params2);

        self::assertEquals($factory->getOtherParameters($dataSource1), $params2);
        self::assertEquals($factory->getOtherParameters($dataSource2), $params1);
        self::assertEquals($factory->getAllParameters(), $result);
    }
}
