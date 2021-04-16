<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM;

use FSi\Component\DataSource\Field\FieldAbstractType;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Exception\DoctrineDriverException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\Options;

use function array_shift;

abstract class DoctrineAbstractField extends FieldAbstractType implements DoctrineFieldInterface
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
        $func = sprintf('and%s', ucfirst($this->getOption('clause')));

        if ('between' === $comparison) {
            if (false === is_array($data)) {
                throw new DoctrineDriverException('Fields with \'between\' comparison require to bind an array.');
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
                $qb->$func($qb->expr()->between($fieldName, ":{$name}_from", ":{$name}_to"));
                $qb->setParameter("{$name}_from", $from, $type);
                $qb->setParameter("{$name}_to", $to, $type);

                return;
            }
        }

        if ('isNull' === $comparison) {
            $qb->$func($fieldName . ' IS ' . ('null' === $data ? '' : 'NOT ') . 'NULL');
            return;
        }

        if (true === in_array($comparison, ['in', 'notIn'], true) && false === is_array($data)) {
            throw new DoctrineDriverException('Fields with \'in\' and \'notIn\' comparisons require to bind an array.');
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

    public function getDBALType(): ?string
    {
        return null;
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
}
