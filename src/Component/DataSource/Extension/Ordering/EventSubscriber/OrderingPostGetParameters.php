<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Ordering\EventSubscriber;

use FSi\Component\DataSource\Event\PostGetParameters;
use FSi\Component\DataSource\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Extension\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Ordering\Storage;

use function array_key_exists;

final class OrderingPostGetParameters implements DataSourceEventSubscriberInterface
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

    public function __invoke(PostGetParameters $event): void
    {
        $dataSource = $event->getDataSource();
        $dataSourceName = $dataSource->getName();
        $parameters = $event->getParameters();

        $sortingParameters = $this->storage->getDataSourceParameters($dataSource->getName());
        if (true === array_key_exists(OrderingExtension::PARAMETER_SORT, $sortingParameters)) {
            $parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT]
                = $sortingParameters[OrderingExtension::PARAMETER_SORT];
        }

        $event->setParameters($parameters);
    }
}
