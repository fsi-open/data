<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType;

use Doctrine\ORM\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\AbstractFieldType;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Exception\DoctrineDriverException;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\EntityTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function sprintf;

class Entity extends AbstractFieldType implements EntityTypeInterface
{
    public function getId(): string
    {
        return 'entity';
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        parent::initOptions($optionsResolver);

        $optionsResolver->setAllowedValues('comparison', ['eq', 'neq', 'memberOf', 'notMemberOf', 'in', 'isNull']);
    }

    public function buildQuery(QueryBuilder $qb, string $alias, FieldInterface $field): void
    {
        if (false === $field->getType() instanceof static) {
            throw new DBALDriverException(
                sprintf(
                    'Field\'s "%s" type "%s" is not compatible with type "%s"',
                    $field->getName(),
                    $field->getType()->getId(),
                    $this->getId()
                )
            );
        }

        $data = $field->getParameter();
        if (true === $this->isEmpty($data)) {
            return;
        }

        $fieldName = $this->getQueryFieldName($field, $alias);
        $name = $field->getName();
        $comparison = $field->getOption('comparison');
        $func = sprintf('and%s', ucfirst($field->getOption('clause')));

        switch ($comparison) {
            case 'eq':
                $qb->$func($qb->expr()->eq($fieldName, ":$name"));
                $qb->setParameter($name, $data);
                break;

            case 'neq':
                $qb->$func($qb->expr()->neq($fieldName, ":$name"));
                $qb->setParameter($name, $data);
                break;

            case 'memberOf':
                $qb->$func(":$name MEMBER OF $fieldName");
                $qb->setParameter($name, $data);
                break;

            case 'notMemberOf':
                $qb->$func(":$name NOT MEMBER OF $fieldName");
                $qb->setParameter($name, $data);
                break;

            case 'in':
                $qb->$func("$fieldName IN (:$name)");
                $qb->setParameter($name, $data);
                break;

            case 'isNull':
                $qb->$func($fieldName . ' IS ' . ($data === 'null' ? '' : 'NOT ') . 'NULL');
                break;

            default:
                throw new DoctrineDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }
    }
}
