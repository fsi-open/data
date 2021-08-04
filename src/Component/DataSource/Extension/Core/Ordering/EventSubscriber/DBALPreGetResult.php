<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Ordering\EventSubscriber;

use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Event\PreGetResult;
use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Extension\Core\Ordering\Storage;

final class DBALPreGetResult implements DataSourceEventSubscriberInterface
{
    private Storage $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public function __invoke(PreGetResult $event): void
    {
        $driver = $event->getDriver();
        if (false === $driver instanceof DBALDriver) {
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
