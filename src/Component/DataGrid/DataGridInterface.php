<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;

interface DataGridInterface
{
    public function getFactory(): DataGridFactoryInterface;

    public function getName(): string;

    /**
     * @param string $name
     * @param string $type
     * @param array<string,mixed> $options
     * @return DataGridInterface
     */
    public function addColumn(string $name, string $type = 'text', array $options = []): DataGridInterface;

    public function addColumnInstance(ColumnInterface $column): DataGridInterface;

    public function removeColumn(string $name): DataGridInterface;

    public function clearColumns(): DataGridInterface;

    public function getColumn(string $name): ColumnInterface;

    /**
     * @return array<ColumnInterface>
     */
    public function getColumns(): array;

    public function hasColumn(string $name): bool;

    public function hasColumnType(string $type): bool;

    public function createView(): DataGridViewInterface;

    /**
     * @param iterable<int|string,array|object> $data
     */
    public function setData(iterable $data): void;

    public function getDataMapper(): DataMapperInterface;
}
