<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Data;

use InvalidArgumentException;
use RuntimeException;

use function array_key_exists;
use function count;
use function current;
use function key;
use function next;
use function reset;

class DataRowset implements DataRowsetInterface
{
    /**
     * @var array<int|string,array<string,mixed>|object>
     */
    protected array $data = [];

    /**
     * @param iterable<int|string,array<string,mixed>|object> $data
     */
    public function __construct(iterable $data)
    {
        foreach ($data as $id => $element) {
            $this->data[$id] = $element;
        }
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return array<string,mixed>|object|false
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * @return int|string|null
     */
    public function key()
    {
        return key($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function rewind(): void
    {
        reset($this->data);
    }

    public function valid(): bool
    {
        return null !== $this->key();
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        if (false === $this->offsetExists($offset)) {
            throw new InvalidArgumentException("Row \"{$offset}\" does not exist in rowset.");
        }

        return $this->data[$offset];
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
