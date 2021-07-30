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

final class EditableDataGridRowView extends DataGridRowView
{
    /**
     * @param EditableDataGridFormHandlerInterface $formHandler
     * @param array<string,ColumnInterface> $columns
     * @param int|string $index
     * @param array<string,mixed>|object $source
     */
    public function __construct(EditableDataGridFormHandlerInterface $formHandler, array $columns, $index, $source)
    {
        parent::__construct($columns, $index, $source);

        foreach ($this->cellViews as $columnName => $cellView) {
            $formHandler->buildCellView($columns[$columnName], $cellView, $index, $source);
        }
    }
}
