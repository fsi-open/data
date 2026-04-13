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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Result;

use function sprintf;

/**
 * @template-implements Result<array<string,mixed>>
 * @internal
 */
final class Paginator4 implements Countable, Result
{
    private Connection $connection;
    private QueryBuilder $query;

    public function __construct(Connection $connection, QueryBuilder $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    /**
     * @return ArrayIterator<int,array<string,mixed>>
     */
    public function getIterator(): ArrayIterator
    {
        $statement = $this->connection->executeQuery(
            $this->query->getSQL(),
            $this->query->getParameters(),
            $this->query->getParameterTypes()
        );

        return new ArrayIterator($statement->fetchAllAssociative());
    }

    public function count(): int
    {
        $sql = (clone $this->query)->setMaxResults(null)->setFirstResult(0)->getSQL();
        $query = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(sprintf('(%s)', $sql), 'orig_query')
        ;

        $statement = $this->connection->executeQuery(
            $query->getSQL(),
            $this->query->getParameters(),
            $this->query->getParameterTypes()
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
