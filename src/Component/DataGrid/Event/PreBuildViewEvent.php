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

final class PreBuildViewEvent
{
    private DataGridInterface $dataGrid;

    public function __construct(DataGridInterface $dataGrid)
    {
        $this->dataGrid = $dataGrid;
    }

    public function getDataGrid(): DataGridInterface
    {
        return $this->dataGrid;
    }
}
