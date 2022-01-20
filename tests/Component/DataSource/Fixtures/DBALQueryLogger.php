<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Fixtures;

use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Event\PreGetResult;
use RuntimeException;

final class DBALQueryLogger
{
    private ?QueryBuilder $queryBuilder = null;

    public function __invoke(PreGetResult $event): void
    {
        $this->queryBuilder = $event->getQueryBuilder();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        if (null === $this->queryBuilder) {
            throw new RuntimeException("No query was executed");
        }

        return $this->queryBuilder;
    }
}
