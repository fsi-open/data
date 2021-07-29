<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use ArrayIterator;
use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Result;

class Paginator implements Result
{
    private QueryBuilder $query;

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->query->execute()->fetchAll());
    }

    public function count(): int
    {
        $query = clone $this->query;
        $query->setFirstResult(0);
        $query->setMaxResults(null);

        $sql = $query->getSQL();
        $query->resetQueryParts(array_keys($query->getQueryParts()));

        $query
            ->select('COUNT(*) count')
            ->from(sprintf('(%s)', $sql), 'orig_query')
        ;

        $row = $query->execute()->fetch();
        return (int) $row['count'];
    }
}
