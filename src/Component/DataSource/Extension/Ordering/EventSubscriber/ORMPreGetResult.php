<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Ordering\EventSubscriber;

use FSi\Component\DataSource\Driver\Doctrine\ORM\ORMDriver;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Event\PreGetResult;
use FSi\Component\DataSource\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Extension\Ordering\Storage;

final class ORMPreGetResult implements DataSourceEventSubscriberInterface
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
        $driver = $event->getDriver();
        if (false === $driver instanceof ORMDriver) {
            return;
        }

        $fields = $event->getFields();
        $sortedFields = $this->storage->sortFields($fields);

        $qb = $event->getQueryBuilder();
        foreach ($sortedFields as $fieldName => $direction) {
            $field = $fields[$fieldName];
            $qb->addOrderBy($driver->getQueryFieldName($field), $direction);
        }
    }
}
