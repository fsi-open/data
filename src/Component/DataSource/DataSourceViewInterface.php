<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

use ArrayAccess;
use Countable;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Util\AttributesContainerInterface;
use IteratorAggregate;
use SeekableIterator;

/**
 * DataSources view is responsible for keeping options needed to build view, fields view objects,
 * and proxy some requests to DataSource.
 */
interface DataSourceViewInterface extends AttributesContainerInterface, ArrayAccess, Countable, SeekableIterator
{
    public function getName(): string;

    /**
     * Returns parameters that were bound to datasource.
     */
    public function getParameters(): array;

    /**
     * Returns parameters that were bound to all datasources.
     */
    public function getAllParameters(): array;

    /**
     * Returns parameters that were bound to other datasources.
     */
    public function getOtherParameters(): array;

    public function hasField(string $name): bool;

    public function removeField(string $name): void;

    public function getField(string $name): FieldViewInterface;

    /**
     * @return array<FieldViewInterface>
     */
    public function getFields(): array;

    public function clearFields(): void;

    public function addField(FieldViewInterface $fieldView): void;

    /**
     * @param array<FieldViewInterface> $fields
     */
    public function setFields(array $fields): void;

    /**
     * @return Countable&IteratorAggregate
     */
    public function getResult(): IteratorAggregate;
}
