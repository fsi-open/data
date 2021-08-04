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
use FSi\Component\DataSource\Driver\Collection\Exception\CollectionDriverException;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Event\PostGetResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Driver\DriverAbstract;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Result;
use Psr\EventDispatcher\EventDispatcherInterface;

use function sprintf;
use function strpos;

class DBALDriver extends DriverAbstract
{
    private QueryBuilder $initialQuery;

    private string $alias;

    /**
     * @var string|Closure|null
     */
    private $indexField;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param array<FieldTypeInterface> $fieldTypes
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string|Closure|null $indexField
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        array $fieldTypes,
        QueryBuilder $queryBuilder,
        string $alias,
        $indexField = null
    ) {
        parent::__construct($eventDispatcher, $fieldTypes);

        $this->initialQuery = $queryBuilder;
        $this->alias = $alias;
        $this->indexField = $indexField;
    }

    /**
     * Constructs proper field name from field mapping or (if absent) from own name.
     * Optionally adds alias (if missing and auto_alias option is set to true).
     */
    public function getQueryFieldName(FieldInterface $field): string
    {
        $name = $field->getOption('field');

        if (true === $field->getOption('auto_alias') && false === strpos($name, ".")) {
            $name = "{$this->alias}.{$name}";
        }

        return $name;
    }

    /**
     * @param array<FieldInterface> $fields
     * @param int|null $first
     * @param int|null $max
     * @return Result
     */
    public function getResult(array $fields, ?int $first, ?int $max): Result
    {
        $query = clone $this->initialQuery;

        $this->getEventDispatcher()->dispatch(new PreGetResult($this, $fields, $query));

        foreach ($fields as $field) {
            $fieldType = $field->getType();
            if (false === $fieldType instanceof DBALFieldInterface) {
                throw new DBALDriverException(
                    sprintf(
                        'Field\'s "%s" type "%s" is not compatible with type "%s"',
                        $field->getName(),
                        $fieldType->getId(),
                        self::class
                    )
                );
            }

            $fieldType->buildQuery($query, $this->alias, $field);
        }

        if (null !== $max) {
            $query->setMaxResults($max);
        }
        if (null !== $first) {
            $query->setFirstResult($first);
        }

        $result = new Paginator($query);
        if (null !== $this->indexField) {
            $result = new DBALResult($result, $this->indexField);
        }
        $event = new PostGetResult($this, $fields, $result);
        $this->getEventDispatcher()->dispatch($event);

        return $event->getResult();
    }
}
