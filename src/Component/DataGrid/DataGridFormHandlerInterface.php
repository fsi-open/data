<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

interface DataGridFormHandlerInterface
{
    /**
     * @param DataGridInterface $dataGrid
     * @param mixed $data
     */
    public function submit(DataGridInterface $dataGrid, $data): void;

    public function isValid(DataGridInterface $dataGrid): bool;
}
