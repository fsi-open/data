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

final class PostSubmitEvent
{
    private DataGridInterface $dataGrid;
    /**
     * @var mixed
     */
    private $data;

    /**
     * @param DataGridInterface $dataGrid
     * @param mixed $data
     */
    public function __construct(DataGridInterface $dataGrid, $data)
    {
        $this->dataGrid = $dataGrid;
        $this->data = $data;
    }

    public function getDataGrid(): DataGridInterface
    {
        return $this->dataGrid;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
