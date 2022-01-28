<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType;

use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Field\Type\AbstractFieldType as CoreAbstractFieldType;
use FSi\Component\DataSource\Field\FieldInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_shift;
use function count;
use function implode;
use function in_array;
use function is_array;
use function sprintf;
use function strpos;
use function ucfirst;

abstract class AbstractFieldType extends CoreAbstractFieldType implements FieldTypeInterface
{
    public function initOptions(OptionsResolver $optionsResolver): void
    {
        parent::initOptions($optionsResolver);

        $optionsResolver
            ->setDefaults([
                'field' => null,
                'auto_alias' => true,
                'clause' => 'where'
            ])
            ->setAllowedValues('clause', ['where', 'having'])
            ->setAllowedTypes('field', ['string', 'null'])
            ->setAllowedTypes('auto_alias', 'bool')
            ->setNormalizer(
                'field',
                static fn(Options $options, ?string $value): ?string => $value ?? $options['name']
            )
            ->setNormalizer(
                'clause',
                static fn(Options $options, string $value): string => strtolower($value)
            )
        ;
    }

    public function getDBALType(): ?string
    {
        return null;
    }

    public function buildQuery(QueryBuilder $qb, string $alias, FieldInterface $field): void
    {
        if (false === $field->getType() instanceof self) {
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

        $type = $this->getDBALType();
        $comparison = $field->getOption('comparison');

        $clause = $field->getOption('clause');
        $func = sprintf('and%s', ucfirst($clause));

        if ('between' === $comparison) {
            if (false === is_array($data)) {
                throw new DBALDriverException('Fields with \'between\' comparison require to bind an array.');
            }

            $from = array_shift($data);
            $to = count($data) ? array_shift($data) : null;

            if (true === $this->isEmpty($from)) {
                $from = null;
            }
            if (true === $this->isEmpty($to)) {
                $to = null;
            }
            if (null === $from && null === $to) {
                return;
            }

            if (null === $from) {
                $comparison = 'lte';
                $data = $to;
            } elseif (null === $to) {
                $comparison = 'gte';
                $data = $from;
            } else {
                $qb->$func("$fieldName BETWEEN :{$name}_from AND :{$name}_to");
                $qb->setParameter("{$name}_from", $from, $type);
                $qb->setParameter("{$name}_to", $to, $type);
                return;
            }
        }

        if ('isNull' === $comparison) {
            $qb->$func($fieldName . ' IS ' . ('null' === $data ? '' : 'NOT ') . 'NULL');
            return;
        }

        if (true === in_array($comparison, ['in', 'notIn'], true)) {
            if (false === is_array($data)) {
                throw new DBALDriverException('Fields with \'in\' and \'notIn\' comparisons require to bind an array.');
            }
            $placeholders = [];
            foreach ($data as $value) {
                $placeholders[] = $qb->createNamedParameter($value, $type);
            }
            $qb->$func($qb->expr()->$comparison($fieldName, implode(', ', $placeholders)));

            return;
        }

        if (true === in_array($comparison, ['like', 'contains'], true)) {
            $data = "%$data%";
            $comparison = 'like';
        }

        $qb->$func($qb->expr()->$comparison($fieldName, ":$name"));
        $qb->setParameter($field->getName(), $data, $type);
    }

    /**
     * Constructs proper field name from field mapping or (if absent) from own name.
     * Optionally adds alias (if missing and auto_alias option is set to true).
     */
    protected function getQueryFieldName(FieldInterface $field, string $alias): string
    {
        $name = $field->getOption('field');

        if (true === $field->getOption('auto_alias') && false === strpos($name, ".")) {
            $name = "{$alias}.{$name}";
        }

        return $name;
    }
}
