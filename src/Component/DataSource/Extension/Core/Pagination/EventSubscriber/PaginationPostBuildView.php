<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber;

use FSi\Component\DataSource\Event\DataSourceEvent\PostBuildView;
use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;

use function ceil;
use function floor;

final class PaginationPostBuildView implements DataSourceEventSubscriberInterface
{
    public static function getPriority(): int
    {
        return 0;
    }

    public function __invoke(PostBuildView $event): void
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
