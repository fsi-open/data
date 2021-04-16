<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;

use function ceil;
use function count;
use function floor;

class Events implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataSourceEvents::PRE_BIND_PARAMETERS => 'preBindParameters',
            DataSourceEvents::POST_GET_PARAMETERS => ['postGetParameters', -1024],
            DataSourceEvents::POST_BUILD_VIEW => 'postBuildView',
        ];
    }

    public function preBindParameters(DataSourceEvent\ParametersEventArgs $event): void
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

    public function postGetParameters(DataSourceEvent\ParametersEventArgs $event): void
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

    public function postBuildView(DataSourceEvent\ViewEventArgs $event): void
    {
        $datasource = $event->getDataSource();
        $datasourceName = $datasource->getName();
        $view = $event->getView();
        $parameters = $view->getParameters();
        $maxResults = $datasource->getMaxResults();

        if (null === $maxResults || 0 === $maxResults) {
            $all = 1;
            $page = 1;
        } else {
            $all = (int) ceil(count($datasource->getResult()) / $maxResults);
            $current = $datasource->getFirstResult();
            $page = (int) floor($current / $maxResults) + 1;
        }

        unset($parameters[$datasourceName][PaginationExtension::PARAMETER_PAGE]);
        $pages = [];

        for ($i = 1; $i <= $all; $i++) {
            if ($i > 1) {
                $parameters[$datasourceName][PaginationExtension::PARAMETER_PAGE] = $i;
            }

            $pages[$i] = $parameters;
        }

        $view->setAttribute('max_results', $maxResults);
        $view->setAttribute('page', $page);
        $view->setAttribute('parameters_pages', $pages);
    }
}
