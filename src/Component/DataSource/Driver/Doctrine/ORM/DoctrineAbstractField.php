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
use FSi\Component\DataSource\Field\FieldAbstractType;
use FSi\Component\DataSource\Field\FieldInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_shift;
use function in_array;
use function is_array;
use function sprintf;
use function strpos;
use function ucfirst;

abstract class DoctrineAbstractField extends FieldAbstractType implements DoctrineFieldInterface
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
            ->setNormalizer('field', function (Options $options, ?string $value): ?string {
                return $value ?? $options['name'];
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

    public function buildQuery(QueryBuilder $qb, string $alias, FieldInterface $field): void
    {
        if (false === $field->getType() instanceof static) {
            throw new DoctrineDriverException(
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
        $func = sprintf('and%s', ucfirst($field->getOption('clause')));

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
            $name = "$alias.$name";
        }

        return $name;
    }
}
