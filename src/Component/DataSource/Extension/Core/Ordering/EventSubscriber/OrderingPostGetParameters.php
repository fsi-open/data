<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Ordering\EventSubscriber;

use FSi\Component\DataSource\Event\DataSourceEvent\PostGetParameters;
use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\Storage;

final class OrderingPostGetParameters implements DataSourceEventSubscriberInterface
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

    public function __invoke(PostGetParameters $event): void
    {
        $dataSource = $event->getDataSource();
        $dataSourceName = $dataSource->getName();
        $parameters = $event->getParameters();

        $sortingParameters = $this->storage->getDataSourceSortingParameters($dataSource);
        if (null !== $sortingParameters) {
            $parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT] = $sortingParameters;
        }

        $event->setParameters($parameters);
    }
}
