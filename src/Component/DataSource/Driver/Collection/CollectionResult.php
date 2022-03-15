<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use FSi\Component\DataSource\Result;

/**
 * @template-implements ArrayAccess<int|string,mixed>
 */
class CollectionResult implements ArrayAccess, Countable, Result
{
    /**
     * @var Collection<int|string,mixed>
     */
    private Collection $collection;
    private int $count;

    /**
     * @param Selectable<int|string,mixed> $collection
     * @param Criteria $criteria
     */
    public function __construct(Selectable $collection, Criteria $criteria)
    {
        $this->collection = $collection->matching($criteria);

        $countCriteria = clone $criteria;
        $countCriteria->setFirstResult(null);
        $countCriteria->setMaxResults(null);
        $this->count = $collection->matching($countCriteria)->count();
    }

    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return ArrayIterator<int|string,mixed>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->collection->toArray());
    }

    public function offsetExists($offset): bool
    {
        return $this->collection->containsKey($offset);
    }

    public function offsetGet($offset)
    {
        return $this->collection->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->collection->add($value);
            return;
        }

        $this->collection->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->collection->remove($offset);
    }
}
