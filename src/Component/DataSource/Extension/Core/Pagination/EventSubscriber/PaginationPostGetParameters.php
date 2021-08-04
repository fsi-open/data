<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber;

use FSi\Component\DataSource\Event\DataSourceEvent\PostGetParameters;
use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;

use function floor;

final class PaginationPostGetParameters implements DataSourceEventSubscriberInterface
{
    public static function getPriority(): int
    {
        return -1024;
    }

    public function __invoke(PostGetParameters $event): void
    {
        $datasource = $event->getDataSource();
        $datasourceName = $datasource->getName();

        $parameters = $event->getParameters();
        $maxResults = $datasource->getMaxResults();

        if (null !== $maxResults && 0 !== $maxResults) {
            $parameters[$datasourceName][PaginationExtension::PARAMETER_MAX_RESULTS] = $maxResults;
            $current = $datasource->getFirstResult();
            $page = (int) floor($current / $maxResults) + 1;
        } else {
            $page = 1;
        }

        unset($parameters[$datasourceName][PaginationExtension::PARAMETER_PAGE]);
        if ($page > 1) {
            $parameters[$datasourceName][PaginationExtension::PARAMETER_PAGE] = $page;
        }

        $event->setParameters($parameters);
    }
}
