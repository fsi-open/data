<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Exception\DoctrineDriverException;
use FSi\Component\DataSource\Driver\DriverAbstract;
use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use FSi\Component\DataSource\Exception\DataSourceException;
use IteratorAggregate;

class DoctrineDriver extends DriverAbstract
{
    /**
     * Alias, that can be used with preconfigured query when fetching one entity and field mappings
     * don't have mappings prefixed with aliases.
     *
     * @var string
     */
    private $alias;

    /**
     * Template query builder.
     *
     * @var QueryBuilder
     */
    private $query;

    /**
     * Query builder available during preGetResult event.
     *
     * @var QueryBuilder|null
     */
    private $currentQuery;

    /**
     * @var bool
     */
    private $useOutputWalkers;

    /**
     * @param array<DriverExtensionInterface> $extensions
     * @param QueryBuilder $queryBuilder
     * @param bool|null $useOutputWalkers
     * @throws DataSourceException
     */
    public function __construct(
        array $extensions,
        QueryBuilder $queryBuilder,
        ?bool $useOutputWalkers = null
    ) {
        parent::__construct($extensions);

        $rootAliases = $queryBuilder->getRootAliases();
        $this->alias = reset($rootAliases);
        $this->query = $queryBuilder;

        $this->useOutputWalkers = $useOutputWalkers;
    }

    public function getType(): string
    {
        return 'doctrine-orm';
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Returns query builder.
     *
     * If current query is set to null (so when getResult method is NOT executed at the moment) exception is thrown.
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if (null === $this->currentQuery) {
            throw new DoctrineDriverException('Query is accessible only during preGetResult event.');
        }

        return $this->currentQuery;
    }

    protected function initResult(): void
    {
        $this->currentQuery = clone $this->query;
    }

    /**
     * @param array<DoctrineFieldInterface> $fields
     * @param int|null $first
     * @param int|null $max
     * @return IteratorAggregate
     * @throws DoctrineDriverException
     */
    protected function buildResult(array $fields, ?int $first, ?int $max): IteratorAggregate
    {
        foreach ($fields as $field) {
            if (false === $field instanceof DoctrineFieldInterface) {
                throw new DoctrineDriverException(sprintf(
                    'All fields must be instances of %s.',
                    DoctrineFieldInterface::class
                ));
            }

            $field->buildQuery($this->currentQuery, $this->alias);
        }

        if (null !== $max || null !== $first) {
            $this->currentQuery->setMaxResults($max);
            $this->currentQuery->setFirstResult($first);
        }

        $result = new Paginator($this->currentQuery);
        $result->setUseOutputWalkers($this->useOutputWalkers);

        $this->currentQuery = null;

        return $result;
    }
}
