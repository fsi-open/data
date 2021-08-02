<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Column;

use FSi\Component\DataGrid\DataGridInterface;

interface ColumnTypeInterface
{
    public function getId(): string;

    /**
     * @param DataGridInterface $dataGrid
     * @param string $name
     * @param array<string,mixed> $options
     * @return ColumnInterface
     */
    public function createColumn(DataGridInterface $dataGrid, string $name, array $options): ColumnInterface;

    /**
     * @param ColumnInterface $column
     * @param int|string $index
     * @param array<string,mixed>|object $source
     * @return CellViewInterface
     */
    public function createCellView(ColumnInterface $column, $index, $source): CellViewInterface;

    public function createHeaderView(ColumnInterface $column): HeaderViewInterface;
}
