<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

interface DataSourceFactoryInterface
{
    public function createDataSource(
        string $driverName,
        array $driverOptions = [],
        string $name = 'datasource'
    ): DataSourceInterface;

    public function addExtension(DataSourceExtensionInterface $extension): void;

    /**
     * @return array<DataSourceExtensionInterface>
     */
    public function getExtensions(): array;

    /**
     * Return array of all parameters from all datasources.
     *
     * @return array
     */
    public function getAllParameters(): array;

    /**
     * Return array of all parameters form all datasources except given.
     *
     * @param DataSourceInterface $datasource
     * @return array
     */
    public function getOtherParameters(DataSourceInterface $datasource): array;

    /**
     * Adds given datasource to list of known datasources, so its data will be fetched
     * during getAllParameters and getOtherParameters.
     *
     * Factory also automatically sets its (datasource) factory to itself.
     *
     * @param DataSourceInterface $datasource
     */
    public function addDataSource(DataSourceInterface $datasource): void;
}
