<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use ArrayAccess;
use Countable;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use Iterator;

interface DataGridViewInterface extends Iterator, Countable, ArrayAccess
{
    public function getName(): string;

    public function hasColumn(string $name): bool;

    public function hasColumnType(string $type): bool;

    public function removeColumn(string $name): void;

    public function getColumn(string $name): HeaderViewInterface;

    /**
     * @return array<HeaderViewInterface>
     */
    public function getColumns(): array;

    public function clearColumns(): void;

    public function addColumn(HeaderViewInterface $column): void;

    /**
     * @param array<HeaderViewInterface> $columns
     */
    public function setColumns(array $columns): void;
}
