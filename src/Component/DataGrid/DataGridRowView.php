<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use InvalidArgumentException;
use RuntimeException;

final class DataGridRowView implements DataGridRowViewInterface
{
    /**
     * @var array<string,CellViewInterface>
     */
    private array $cellViews = [];
    /**
     * @var array<string,mixed>|object
     */
    private $source;
    /**
     * @var int|string
     */
    private $index;

    /**
     * @param array<string,ColumnInterface> $columns
     * @param int|string $index
     * @param array<string,mixed>|object $source
     */
    public function __construct(array $columns, $index, $source)
    {
        $this->source = $source;
        $this->index = $index;
        foreach ($columns as $name => $column) {
            if (false === $column instanceof ColumnInterface) {
                throw new UnexpectedTypeException(sprintf(
                    'Column object must implement "%s"',
                    ColumnInterface::class
                ));
            }

            $this->cellViews[$name] = $column->getDataGrid()->getFactory()->createCellView($column, $index, $source);
        }
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function count(): int
    {
        return count($this->cellViews);
    }

    /**
     * @return CellViewInterface|false
     */
    public function current()
    {
        return current($this->cellViews);
    }

    /**
     * @return string|null
     */
    public function key()
    {
        return key($this->cellViews);
    }

    public function next(): void
    {
        next($this->cellViews);
    }

    public function rewind(): void
    {
        reset($this->cellViews);
    }

    public function valid(): bool
    {
        return $this->key() !== null;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->cellViews);
    }

    public function offsetGet($offset): CellViewInterface
    {
        if ($this->offsetExists($offset)) {
            return $this->cellViews[$offset];
        }

        throw new InvalidArgumentException(sprintf('Column "%s" does not exist in row.', $offset));
    }

    public function offsetSet($offset, $value): void
    {
        throw new RuntimeException('Method not implemented');
    }

    public function offsetUnset($offset): void
    {
        throw new RuntimeException('Method not implemented');
    }
}
