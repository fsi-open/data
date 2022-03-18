<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Pagination\EventSubscriber;

use Countable;
use FSi\Component\DataSource\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Event\PostBuildView;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Extension\Pagination\PaginationExtension;
use FSi\Component\DataSource\Result;

use function ceil;
use function count;
use function floor;
use function get_class;

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
        $maxResults = $datasource->getMaxResults();
        $result = $datasource->getResult();
        if (false === $result instanceof Countable) {
            $this->throwExceptionIfMaxResultsSetForUncountable($maxResults, $datasourceName, $result);
            return;
        }

        $view = $event->getView();
        $parameters = $view->getParameters();

        if (true === $this->hasMaxResultsParameter($maxResults)) {
            $all = (int) ceil(count($result) / $maxResults);
            $page = (int) floor($datasource->getFirstResult() / $maxResults) + 1;
        } else {
            $all = 1;
            $page = 1;
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

    private function throwExceptionIfMaxResultsSetForUncountable(
        ?int $maxResults,
        string $datasourceName,
        Result $result
    ): void {
        if (true === $this->hasMaxResultsParameter($maxResults)) {
            throw new DataSourceException(sprintf(
                'DataSource\'s "%s" result of class "%s" is not countable, but has max results set',
                $datasourceName,
                get_class($result)
            ));
        }
    }

    private function hasMaxResultsParameter(?int $maxResults): bool
    {
        return null !== $maxResults && 0 !== $maxResults;
    }
}
