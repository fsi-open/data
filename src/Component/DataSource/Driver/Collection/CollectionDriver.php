<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use FSi\Component\DataSource\Driver\DriverAbstract;
use FSi\Component\DataSource\Driver\Collection\Exception\CollectionDriverException;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Result;
use IteratorAggregate;
use Traversable;

class CollectionDriver extends DriverAbstract
{
    /**
     * @var Selectable
     */
    private $collection;

    /**
     * @var Criteria
     */
    private $baseCriteria;

    /**
     * Criteria available during preGetResult event.
     *
     * @var Criteria
     */
    private $currentCriteria;

    public function __construct(array $extensions, Selectable $collection, ?Criteria $criteria = null)
    {
        parent::__construct($extensions);

        $this->collection = $collection;
        $this->baseCriteria = $criteria ?? Criteria::create();
    }

    public function getType(): string
    {
        return 'collection';
    }

    /**
     * Returns criteria.
     *
     * If criteria is set to null (so when getResult method is NOT executed at the moment) exception is thrown.
     */
    public function getCriteria(): Criteria
    {
        if (null === $this->currentCriteria) {
            throw new CollectionDriverException('Criteria is accessible only during preGetResult event.');
        }

        return $this->currentCriteria;
    }

    /**
     * @param array<CollectionFieldInterface> $fields
     * @param int|null $first
     * @param int|null $max
     * @return CollectionResult
     * @throws CollectionDriverException
     */
    protected function buildResult(array $fields, ?int $first, ?int $max): Result
    {
        foreach ($fields as $field) {
            if (false === $field instanceof CollectionFieldInterface) {
                throw new CollectionDriverException(
                    sprintf('All fields must be instances of %s', CollectionFieldInterface::class)
                );
            }

            $field->buildCriteria($this->currentCriteria);
        }

        if (null !== $max || null !== $first) {
            $this->currentCriteria->setMaxResults($max);
            $this->currentCriteria->setFirstResult($first);
        }

        return new CollectionResult($this->collection, $this->currentCriteria);
    }

    protected function initResult(): void
    {
        $this->currentCriteria = clone $this->baseCriteria;
    }
}
