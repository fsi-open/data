<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Field\FieldAbstractType;
use Symfony\Component\OptionsResolver\Options;

use function array_shift;

abstract class DBALAbstractField extends FieldAbstractType implements DBALFieldInterface
{
    public function buildQuery(QueryBuilder $qb, string $alias): void
    {
        $data = $this->getCleanParameter();
        $fieldName = $this->getFieldName($alias);
        $name = $this->getName();

        if (true === $this->isEmpty($data)) {
            return;
        }

        $type = $this->getDBALType();
        $comparison = $this->getComparison();

        $clause = $this->getOption('clause');
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
        $qb->setParameter($this->getName(), $data, $type);
    }

    public function initOptions(): void
    {
        $this->getOptionsResolver()
            ->setDefaults([
                'field' => null,
                'auto_alias' => true,
                'clause' => 'where'
            ])
            ->setAllowedValues('clause', ['where', 'having'])
            ->setAllowedTypes('field', ['string', 'null'])
            ->setAllowedTypes('auto_alias', 'bool')
            ->setNormalizer('field', function (Options $options, ?string $value): ?string {
                return $value ?? $this->getName();
            })
            ->setNormalizer('clause', function (Options $options, string $value): string {
                return strtolower($value);
            })
        ;
    }

    /**
     * Constructs proper field name from field mapping or (if absent) from own name.
     * Optionally adds alias (if missing and auto_alias option is set to true).
     */
    protected function getFieldName(string $alias): string
    {
        $name = $this->getOption('field');

        if (true === $this->getOption('auto_alias') && false === strpos($name, ".")) {
            $name = "$alias.$name";
        }

        return $name;
    }

    public function getDBALType(): ?string
    {
        return null;
    }
}
