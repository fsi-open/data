<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Column\ColumnTypeInterface;

interface DataGridExtensionInterface
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
}
