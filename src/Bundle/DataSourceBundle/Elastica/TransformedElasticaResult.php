<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\Elastica;

use ArrayIterator;
use Countable;
use FSi\Component\DataSource\Result;
use Traversable;

/**
 * @template T
 * @implements Result<T>
 */
final class TransformedElasticaResult implements Countable, Result
{
    /**
     * @var int<0, max>
     */
    private int $count;
    /**
     * @var array<int|string, T>
     */
    private array $objects;
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $aggregations;

    /**
     * @param int<0, max> $count
     * @param array<int|string, T> $objects
     * @param array<string, array<string, mixed>> $aggregations
     */
    public function __construct(int $count, array $objects, array $aggregations)
    {
        $this->count = $count;
        $this->objects = $objects;
        $this->aggregations = $aggregations;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->objects);
    }

    /**
     * @param string $name
     * @return array<string, mixed>
     */
    public function getAggregation(string $name): array
    {
        return $this->aggregations[$name];
    }
}
