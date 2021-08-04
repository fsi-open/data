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

final class PreSetDataEvent
{
    private DataGridInterface $dataGrid;
    /**
     * @var iterable<int|string,array<string,mixed>|object>
     */
    private iterable $data;

    /**
     * @param DataGridInterface $dataGrid
     * @param iterable<int|string,array<string,mixed>|object> $data
     */
    public function __construct(DataGridInterface $dataGrid, iterable $data)
    {
        $this->dataGrid = $dataGrid;
        $this->data = $data;
    }

    public function getDataGrid(): DataGridInterface
    {
        return $this->dataGrid;
    }

    /**
     * @return iterable<int|string,array<string,mixed>|object>
     */
    public function getData(): iterable
    {
        return $this->data;
    }

    /**
     * @param iterable<int|string,array<string,mixed>|object> $data
     */
    public function setData(iterable $data): void
    {
        $this->data = $data;
    }
}
