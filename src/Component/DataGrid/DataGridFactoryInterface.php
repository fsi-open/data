<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;

interface DataGridFactoryInterface
{
    /**
     * @param string|class-string<ColumnTypeInterface> $type
     * @return bool
     */
    public function hasColumnType(string $type): bool;

    /**
     * @param string|class-string<ColumnTypeInterface> $type
     * @return ColumnTypeInterface
     */
    public function getColumnType(string $type): ColumnTypeInterface;

    /**
     * @param ColumnTypeInterface $columnType
     * @return array<ColumnTypeExtensionInterface>
     */
    public function getColumnTypeExtensions(ColumnTypeInterface $columnType): array;

    public function getDataMapper(): DataMapperInterface;

    public function createDataGrid(string $name): DataGridInterface;

    /**
     * @param DataGridInterface $dataGrid
     * @param string|class-string<ColumnTypeInterface> $type
     * @param string $name
     * @param array<string,mixed> $options
     * @return ColumnInterface
     */
    public function createColumn(
        DataGridInterface $dataGrid,
        string $type,
        string $name,
        array $options
    ): ColumnInterface;

    /**
     * @param ColumnInterface $column
     * @param array|object $source
     * @return CellViewInterface
     */
    public function createCellView(ColumnInterface $column, $source): CellViewInterface;

    public function createHeaderView(ColumnInterface $column): HeaderViewInterface;
}
