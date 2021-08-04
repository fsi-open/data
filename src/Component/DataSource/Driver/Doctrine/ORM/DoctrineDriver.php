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
use FSi\Component\DataSource\Driver\DriverAbstract;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Result;
use Psr\EventDispatcher\EventDispatcherInterface;

use function sprintf;
use function strpos;

class DoctrineDriver extends DriverAbstract
{
    private ManagerRegistry $managerRegistry;

    private string $alias;

    private QueryBuilder $query;

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
        ?bool $useOutputWalkers = null
    ) {
        parent::__construct($eventDispatcher, $fieldTypes);

        $this->managerRegistry = $managerRegistry;
        $rootAliases = $queryBuilder->getRootAliases();
        $alias = reset($rootAliases);
        if (false === $alias) {
            throw new DoctrineDriverException("Doctrine ORM initial query does not have any root aliases");
        }

        $this->alias = $alias;
        $this->query = $queryBuilder;

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
            if (false === $fieldType instanceof DoctrineFieldInterface) {
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

        $result = new Paginator($query);
        $result->setUseOutputWalkers($this->useOutputWalkers);

        $event = new PostGetResult($this, $fields, new DoctrineResult($this->managerRegistry, $result));
        $this->getEventDispatcher()->dispatch($event);

        return $result;
    }
}
