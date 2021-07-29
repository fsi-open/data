<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Event;

use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataGridViewInterface;

final class PostBuildViewEvent
{
    private DataGridInterface $dataGrid;
    private DataGridViewInterface $dataGridView;

    public function __construct(DataGridInterface $dataGrid, DataGridViewInterface $dataGridView)
    {
        $this->dataGrid = $dataGrid;
        $this->dataGridView = $dataGridView;
    }

    public function getDataGrid(): DataGridInterface
    {
        return $this->dataGrid;
    }

    public function getDataGridView(): DataGridViewInterface
    {
        return $this->dataGridView;
    }
}
