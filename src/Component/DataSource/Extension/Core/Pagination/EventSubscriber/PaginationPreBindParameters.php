<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber;

use FSi\Component\DataSource\Event\DataSourceEvent\PreBindParameters;
use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;

final class PaginationPreBindParameters implements DataSourceEventSubscriberInterface
{
    public static function getPriority(): int
    {
        return 0;
    }

    public function __invoke(PreBindParameters $event): void
    {
        $datasource = $event->getDataSource();
        $parameters = $event->getParameters();

        $resultsPerPage = $parameters[$datasource->getName()][PaginationExtension::PARAMETER_MAX_RESULTS]
            ?? $datasource->getMaxResults();
        if (null !== $resultsPerPage) {
            $resultsPerPage = (int) $resultsPerPage;
        }

        $datasource->setMaxResults($resultsPerPage);

        $page = (int) ($parameters[$datasource->getName()][PaginationExtension::PARAMETER_PAGE] ?? 1);

        $datasource->setFirstResult(($page - 1) * $datasource->getMaxResults());
    }
}
