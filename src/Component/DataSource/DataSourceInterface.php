<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

use Countable;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use IteratorAggregate;

/**
 * DataSource abstracts fetching data from various sources. For more information
 * about usage please view README file.
 *
 * DataSource maintains communication with driver, manipulating fields (adding,
 * removing, etc.), calling DataSource extensions events, view creation and more.
 * It's first and main interface client will communicate with.
 */
interface DataSourceInterface
{
    /**
     * Key for fields data.
     */
    public const PARAMETER_FIELDS = 'fields';

    public function getName(): string;

    public function hasField(string $name): bool;

    /**
     * Adds field to data source.
     *
     * Keep in mind, that this method should be able to add field object, if such given as first argument. If so,
     * $type and $comparison are mandatory and it's up to implementation to check whether are given.
     *
     * @param object|string $name
     * @param string|null $type
     * @param string|null $comparison
     * @param array $options
     * @return DataSourceInterface
     * @throws DataSourceException
     */
    public function addField(
        $name,
        ?string $type = null,
        ?string $comparison = null,
        array $options = []
    ): DataSourceInterface;

    public function removeField(string $name): void;

    public function getField(string $name): FieldTypeInterface;

    /**
     * @return array<FieldTypeInterface>
     * @throws DataSourceException
     */
    public function getFields(): array;

    public function clearFields(): void;

    public function setMaxResults(?int $max): DataSourceInterface;

    public function setFirstResult(?int $first): DataSourceInterface;

    public function getMaxResults(): ?int;

    public function getFirstResult(): ?int;

    /**
     * Binds parameters to datasource. These could be any type of data that will be converted to array i.e by some
     * event subscriber
     *
     * @param mixed $parameters
     */
    public function bindParameters($parameters = []): void;

    public function getResult(): Result;

    public function addExtension(DataSourceExtensionInterface $extension): void;

    /**
     * @return array<DataSourceExtensionInterface>
     */
    public function getExtensions(): array;

    public function createView(): DataSourceViewInterface;

    public function getParameters(): array;

    /**
     * Returns all parameters from all datasources on page.
     *
     * Works properly only if factory is assigned, or just created through factory,
     * and all others datasources were created through that factory. Otherwise (if
     * no factory assigned, or if it's the only one datasource that far) it will
     * return the same result as getParameters method.
     *
     * @return array
     */
    public function getAllParameters(): array;

    /**
     * Returns all parameters from all datasources on page except this one.
     *
     * Constraints similars to these of getAllParameters method - if no factory
     * assigned, method will return empty array.
     *
     * @return array
     */
    public function getOtherParameters(): array;

    public function setFactory(DataSourceFactoryInterface $factory): void;

    public function getFactory(): ?DataSourceFactoryInterface;
}
