<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Event;

use FSi\Component\DataGrid\Data\DataRowsetInterface;
use FSi\Component\DataGrid\DataGridInterface;

final class PostSetDataEvent
{
    private DataGridInterface $dataGrid;
    private DataRowsetInterface $data;

    public function __construct(DataGridInterface $dataGrid, DataRowsetInterface $data)
    {
        $this->dataGrid = $dataGrid;
        $this->data = $data;
    }

    public function getDataGrid(): DataGridInterface
    {
        return $this->dataGrid;
    }

    public function getData(): DataRowsetInterface
    {
        return $this->data;
    }
}
