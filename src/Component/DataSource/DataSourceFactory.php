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

use function array_key_exists;

class DataSourceFactory implements DataSourceFactoryInterface
{
    /**
     * @var array<DataSourceInterface>
     */
    protected $datasources = [];

    /**
     * @var DriverFactoryManagerInterface
     */
    protected $driverFactoryManager;

    /**
     * @var array<DataSourceExtensionInterface>
     */
    protected $extensions = [];

    /**
     * @param DriverFactoryManagerInterface $driverFactoryManager
     * @param array<DataSourceExtensionInterface> $extensions
     * @throws DataSourceException
     */
    public function __construct(DriverFactoryManagerInterface $driverFactoryManager, array $extensions = [])
    {
        $this->driverFactoryManager = $driverFactoryManager;

        foreach ($extensions as $extension) {
            if (false === $extension instanceof DataSourceExtensionInterface) {
                throw new DataSourceException(sprintf(
                    'Instance of %s expected, "%s" given.',
                    DataSourceExtensionInterface::class,
                    is_object($extension) ? get_class($extension) : gettype($extension)
                ));
            }
        }

        $this->extensions = $extensions;
    }

    public function createDataSource(
        string $driverName,
        array $driverOptions = [],
        string $name = 'datasource'
    ): DataSourceInterface {
        $driverFactory = $this->driverFactoryManager->getFactory($driverName);
        $driver = $driverFactory->createDriver($driverOptions);

        $this->checkDataSourceName($name);

        $datasource = new DataSource($driver, $name);
        $this->datasources[$name] = $datasource;

        foreach ($this->extensions as $extension) {
            $datasource->addExtension($extension);
        }

        $datasource->setFactory($this);

        return $datasource;
    }

    public function addExtension(DataSourceExtensionInterface $extension): void
    {
        $this->extensions[] = $extension;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getAllParameters(): array
    {
        $result = [];
        foreach ($this->datasources as $datasource) {
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
        foreach ($this->datasources as $datasource) {
            if ($datasource !== $except) {
                $result[] = $datasource->getParameters();
            }
        }

        if (0 !== count($result)) {
            return array_merge_recursive(...$result);
        }

        return $result;
    }

    public function addDataSource(DataSourceInterface $datasource): void
    {
        $name = $datasource->getName();
        $this->checkDataSourceName($name, $datasource);
        $this->datasources[$name] = $datasource;
        $datasource->setFactory($this);
    }

    private function checkDataSourceName(string $name, ?DataSourceInterface $datasource = null): void
    {
        if ('' === $name) {
            throw new DataSourceException('Name of data source can\'t be empty.');
        }

        if (true === array_key_exists($name, $this->datasources) && ($this->datasources[$name] !== $datasource)) {
            throw new DataSourceException('Name of data source must be unique.');
        }

        if (1 !== preg_match('/^[\w]+$/', $name)) {
            throw new DataSourceException('Name of data source may contain only word characters and digits.');
        }
    }
}
