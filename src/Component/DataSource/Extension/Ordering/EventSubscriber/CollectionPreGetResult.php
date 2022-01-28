<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Ordering\EventSubscriber;

use FSi\Component\DataSource\Driver\Collection\Event\PreGetResult;
use FSi\Component\DataSource\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Extension\Ordering\Storage;

use function strtoupper;

final class CollectionPreGetResult implements DataSourceEventSubscriberInterface
{
    private Storage $storage;

    public static function getPriority(): int
    {
        return 0;
    }

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function __invoke(PreGetResult $event): void
    {
        $fields = $event->getFields();
        $sortedFields = $this->storage->sortFields($fields);

        $criteria = $event->getCriteria();
        $orderings = $criteria->getOrderings();
        foreach ($sortedFields as $fieldName => $direction) {
            $field = $fields[$fieldName];
            $fieldName = true === $field->hasOption('field') ? $field->getOption('field') : $field->getName();
            $orderings[$fieldName] = strtoupper($direction);
        }
        $criteria->orderBy($orderings);
    }
}
