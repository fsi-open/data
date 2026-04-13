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
use Countable;
use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Result;

use function sprintf;

/**
 * @template-implements Result<array<string,mixed>>
 * @deprecated since 1.3 and will be removed in 2.0 along with DBAL ^3.0 support
 */
final class Paginator implements Countable, Result
{
    private QueryBuilder $query;

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * @return ArrayIterator<int,array<string,mixed>>
     */
    public function getIterator(): ArrayIterator
    {
        $statement = $this->query->getConnection()->executeQuery(
            $this->query->getSQL(),
            $this->query->getParameters(),
            $this->query->getParameterTypes()
        );

        return new ArrayIterator($statement->fetchAllAssociative());
    }

    public function count(): int
    {
        $query = clone $this->query;
        $query->setFirstResult(0);
        $query->setMaxResults(null);

        $sql = $query->getSQL();
        $query->resetQueryParts(array_keys($query->getQueryParts()));

        $query
            ->select('COUNT(*)')
            ->from(sprintf('(%s)', $sql), 'orig_query')
        ;

        $statement = $query->getConnection()->executeQuery(
            $query->getSQL(),
            $query->getParameters(),
            $query->getParameterTypes()
        );

        $count = (int) $statement->fetchOne();
        if (0 > $count) {
            throw new DBALDriverException(
                sprintf('Count query should return non-negative integer, but %d was returned', $count)
            );
        }

        return $count;
    }
}
