<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver;

use Countable;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Result;
use IteratorAggregate;

/**
 * Driver is responsible for fetching data based on passed fields and data.
 */
interface DriverInterface
{
    /**
     * Returns type (name) of this driver.
     */
    public function getType(): string;

    /**
     * Sets reference to DataSource.
     */
    public function setDataSource(DataSourceInterface $datasource): void;

    /**
     * Return reference to assigned DataSource.
     */
    public function getDataSource(): DataSourceInterface;

    /**
     * Checks if driver has field for given type.
     */
    public function hasFieldType(string $type): bool;

    /**
     * Return field for given type.
     */
    public function getFieldType(string $type): FieldTypeInterface;

    /**
     * Returns collection with result.
     *
     * Returned object must implement interfaces Countable and IteratorAggregate.
     * Count on this object must return amount
     * of all available results.
     *
     * @param array<FieldTypeInterface> $fields
     * @param int|null $first
     * @param int|null $max
     * @return Result
     */
    public function getResult(array $fields, ?int $first, ?int $max): Result;

    /**
     * Returns loaded extensions.
     *
     * @return array<DriverExtensionInterface>
     */
    public function getExtensions(): array;

    /**
     * Adds extension to driver.
     */
    public function addExtension(DriverExtensionInterface $extension): void;
}
