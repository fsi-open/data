<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Ordering\EventSubscriber;

use FSi\Component\DataSource\Event\PreBindParameters;
use FSi\Component\DataSource\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Extension\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Ordering\Storage;

use function array_key_exists;
use function in_array;
use function is_array;
use function sprintf;

final class OrderingPreBindParameters implements DataSourceEventSubscriberInterface
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

    public function __invoke(PreBindParameters $event): void
    {
        $dataSource = $event->getDataSource();
        $dataSourceName = $dataSource->getName();
        $parameters = $event->getParameters();

        if (
            true === array_key_exists($dataSourceName, $parameters)
            && true === is_array($parameters[$dataSourceName])
            && true === array_key_exists(OrderingExtension::PARAMETER_SORT, $parameters[$dataSourceName])
            && true === is_array($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT])
        ) {
            $priority = 0;
            $sortingParameters = $parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT];
            foreach ($sortingParameters as $fieldName => $direction) {
                if (false === in_array($direction, ['asc', 'desc'])) {
                    throw new DataSourceException(sprintf("Unknown sorting direction %s specified", $direction));
                }

                $field = $dataSource->getField($fieldName);
                $this->storage->setFieldSorting($field, $priority, 'asc' === $direction);
                $priority++;
            }

            $this->storage->setDataSourceSortingParameters($dataSource, $sortingParameters);
        }
    }
}
