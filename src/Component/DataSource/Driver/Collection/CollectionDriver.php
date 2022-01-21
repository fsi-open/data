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
use FSi\Component\DataSource\Driver\Collection\Event\PostGetResult;
use FSi\Component\DataSource\Driver\Collection\Event\PreGetResult;
use FSi\Component\DataSource\Driver\DriverAbstract;
use FSi\Component\DataSource\Driver\Collection\Exception\CollectionDriverException;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Result;
use Psr\EventDispatcher\EventDispatcherInterface;

class CollectionDriver extends DriverAbstract
{
    /**
     * @var Selectable<int|string,mixed>
     */
    private Selectable $collection;
    private Criteria $baseCriteria;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param array<FieldTypeInterface> $fieldTypes
     * @param Selectable<int|string,mixed> $collection
     * @param Criteria|null $criteria
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        array $fieldTypes,
        Selectable $collection,
        ?Criteria $criteria
    ) {
        parent::__construct($eventDispatcher, $fieldTypes);

        $this->collection = $collection;
        $this->baseCriteria = $criteria ?? Criteria::create();
    }

    /**
     * @param array<FieldInterface> $fields
     * @param int|null $first
     * @param int|null $max
     * @return Result
     * @throws CollectionDriverException
     */
    public function getResult(array $fields, ?int $first, ?int $max): Result
    {
        $criteria = clone $this->baseCriteria;

        $this->getEventDispatcher()->dispatch(new PreGetResult($this, $fields, $criteria));

        foreach ($fields as $field) {
            $fieldType = $field->getType();
            if (false === $fieldType instanceof CollectionFieldInterface) {
                throw new CollectionDriverException(
                    sprintf(
                        'Field\'s "%s" type "%s" is not compatible with type "%s"',
                        $field->getName(),
                        $fieldType->getId(),
                        self::class
                    )
                );
            }

            $fieldType->buildCriteria($criteria, $field);
        }

        if (null !== $max || null !== $first) {
            $criteria->setMaxResults($max);
            $criteria->setFirstResult($first);
        }

        $event = new PostGetResult($this, $fields, new CollectionResult($this->collection, $criteria));
        $this->getEventDispatcher()->dispatch($event);

        return $event->getResult();
    }
}
