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

interface EditableDataGridFormHandlerInterface
{
    /**
     * @param ColumnInterface $column
     * @param int|string $index
     * @param array<string,mixed>|object $object
     * @param mixed $data
     */
    public function bindData(ColumnInterface $column, $index, $object, $data): void;

    /**
     * @param ColumnInterface $column
     * @param CellViewInterface $view
     * @param int|string $index
     * @param array<string,mixed>|object $object
     */
    public function buildCellView(ColumnInterface $column, CellViewInterface $view, $index, $object): void;
}
