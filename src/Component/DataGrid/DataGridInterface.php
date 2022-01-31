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
use FSi\Component\DataGrid\Column\ColumnInterface;
use Iterator;

/**
 * @template-extends ArrayAccess<int|string,array<string,mixed>|object>
 * @template-extends Iterator<int|string,array<string,mixed>|object>
 */
interface DataGridInterface extends ArrayAccess, Countable, Iterator
{
    public function getFactory(): DataGridFactoryInterface;

    public function getName(): string;

    /**
     * @return array<ColumnInterface>
     */
    public function getColumns(): array;

    public function hasColumn(string $name): bool;

    public function hasColumnType(string $type): bool;

    public function getColumn(string $name): ColumnInterface;

    /**
     * @param string $name
     * @param string $type
     * @param array<string,mixed> $options
     * @return DataGridInterface
     */
    public function addColumn(string $name, string $type, array $options = []): DataGridInterface;

    public function addColumnInstance(ColumnInterface $column): DataGridInterface;

    public function removeColumn(string $name): DataGridInterface;

    public function clearColumns(): void;

    public function createView(): DataGridViewInterface;

    /**
     * @param iterable<int|string,array<string,mixed>|object> $data
     */
    public function setData(iterable $data): void;
}
