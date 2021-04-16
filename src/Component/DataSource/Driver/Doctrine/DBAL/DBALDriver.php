<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Closure;
use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Driver\DriverAbstract;
use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use FSi\Component\DataSource\Exception\DataSourceException;
use IteratorAggregate;

class DBALDriver extends DriverAbstract
{
    /**
     * Alias, that can be used with preconfigured query when fetching one entity and field mappings
     * don't have mappings prefixed with aliases.
     *
     * @var string
     */
    private $alias;

    /**
     * @var QueryBuilder
     */
    private $initialQuery;

    /**
     * Query builder available during preGetResult event.
     *
     * @var QueryBuilder|null
     */
    private $currentQuery;

    /**
     * @var string|Closure|null
     */
    private $indexField;

    /**
     * @param array<DriverExtensionInterface> $extensions
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string|Closure|null $indexField
     * @throws DataSourceException
     */
    public function __construct(
        array $extensions,
        QueryBuilder $queryBuilder,
        string $alias,
        $indexField = null
    ) {
        parent::__construct($extensions);

        $this->initialQuery = $queryBuilder;
        $this->alias = $alias;
        $this->indexField = $indexField;
    }

    public function getType(): string
    {
        return 'doctrine-dbal';
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Returns query builder.
     *
     * If query builder is set to null (so when getResult method is NOT executed at the moment) exception is thrown.
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if (null === $this->currentQuery) {
            throw new DBALDriverException('Query is accessible only during preGetResult event.');
        }

        return $this->currentQuery;
    }

    /**
     * @return Closure|string|null
     */
    public function getIndexField()
    {
        return $this->indexField;
    }

    protected function initResult(): void
    {
        $this->currentQuery = clone $this->initialQuery;
    }

    /**
     * @param array<DBALFieldInterface> $fields
     * @param int|null $first
     * @param int|null $max
     * @return IteratorAggregate
     * @throws DBALDriverException
     */
    protected function buildResult(array $fields, ?int $first, ?int $max): IteratorAggregate
    {
        foreach ($fields as $field) {
            if (false === $field instanceof DBALFieldInterface) {
                throw new DBALDriverException(
                    sprintf('All fields must be instances of %s.', DBALFieldInterface::class)
                );
            }

            $field->buildQuery($this->currentQuery, $this->alias);
        }

        if (null !== $max || null !== $first) {
            $this->currentQuery->setMaxResults($max);
            $this->currentQuery->setFirstResult($first);
        }

        $paginator = new Paginator($this->currentQuery);

        $this->currentQuery = null;

        return $paginator;
    }
}
