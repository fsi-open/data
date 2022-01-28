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
use Doctrine\Persistence\ManagerRegistry;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Event\PostGetResult;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Exception\DoctrineDriverException;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\FieldTypeInterface;
use FSi\Component\DataSource\Driver\DriverAbstract;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Result;
use Psr\EventDispatcher\EventDispatcherInterface;

use function sprintf;
use function strpos;

final class ORMDriver extends DriverAbstract
{
    private ManagerRegistry $managerRegistry;
    private QueryBuilder $query;
    private string $alias;
    private ?bool $useOutputWalkers;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param array<FieldTypeInterface> $fieldTypes
     * @param QueryBuilder $queryBuilder
     * @param bool|null $useOutputWalkers
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        EventDispatcherInterface $eventDispatcher,
        array $fieldTypes,
        QueryBuilder $queryBuilder,
        ?bool $useOutputWalkers
    ) {
        parent::__construct($eventDispatcher, $fieldTypes);

        $this->managerRegistry = $managerRegistry;
        $rootAliases = $queryBuilder->getRootAliases();
        $alias = reset($rootAliases);
        if (false === $alias) {
            throw new DoctrineDriverException("Doctrine ORM initial query does not have any root aliases");
        }

        $this->query = $queryBuilder;
        $this->alias = $alias;
        $this->useOutputWalkers = $useOutputWalkers;
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
        $query = clone $this->query;

        $this->getEventDispatcher()->dispatch(new PreGetResult($this, $fields, $query));

        foreach ($fields as $field) {
            $fieldType = $field->getType();
            if (false === $fieldType instanceof FieldTypeInterface) {
                throw new DoctrineDriverException(
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

        if (null !== $max || null !== $first) {
            $query->setMaxResults($max);
            $query->setFirstResult($first);
        }

        $result = new ORMPaginator($query);
        $result->setUseOutputWalkers($this->useOutputWalkers);

        $event = new PostGetResult($this, $fields, new ORMResult($this->managerRegistry, $result));
        $this->getEventDispatcher()->dispatch($event);

        return $result;
    }
}
