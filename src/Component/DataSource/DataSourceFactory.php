<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

use FSi\Component\DataSource\Driver\DriverFactoryManagerInterface;
use FSi\Component\DataSource\Exception\DataSourceException;
use Psr\EventDispatcher\EventDispatcherInterface;

use function array_key_exists;

class DataSourceFactory implements DataSourceFactoryInterface
{
    private EventDispatcherInterface $eventDispatcher;

    private DriverFactoryManagerInterface $driverFactoryManager;

    /**
     * @var array<DataSourceInterface>
     */
    private array $dataSources = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DriverFactoryManagerInterface $driverFactoryManager
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->driverFactoryManager = $driverFactoryManager;
    }

    public function createDataSource(
        string $driverName,
        array $driverOptions = [],
        string $name = 'datasource'
    ): DataSourceInterface {
        $driver = $this->driverFactoryManager->getFactory($driverName)->createDriver($driverOptions);

        $this->checkDataSourceName($name);

        $datasource = new DataSource($name, $this, $this->eventDispatcher, $driver);
        $this->dataSources[$name] = $datasource;

        return $datasource;
    }

    public function getAllParameters(): array
    {
        $result = [];
        foreach ($this->dataSources as $datasource) {
            $result[] = $datasource->getParameters();
        }

        if (0 !== count($result)) {
            return array_merge(...$result);
        }

        return $result;
    }

    public function getOtherParameters(DataSourceInterface $except): array
    {
        $result = [];
        foreach ($this->dataSources as $datasource) {
            if ($datasource !== $except) {
                $result[] = $datasource->getParameters();
            }
        }

        if (0 !== count($result)) {
            return array_merge_recursive(...$result);
        }

        return $result;
    }

    private function checkDataSourceName(string $name, ?DataSourceInterface $datasource = null): void
    {
        if ('' === $name) {
            throw new DataSourceException('Name of data source can\'t be empty.');
        }

        if (true === array_key_exists($name, $this->dataSources) && ($this->dataSources[$name] !== $datasource)) {
            throw new DataSourceException('Name of data source must be unique.');
        }

        if (1 !== preg_match('/^[\w]+$/', $name)) {
            throw new DataSourceException('Name of data source may contain only word characters and digits.');
        }
    }
}
