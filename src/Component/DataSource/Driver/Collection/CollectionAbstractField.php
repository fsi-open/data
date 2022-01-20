<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection;

use Doctrine\Common\Collections\Criteria;
use FSi\Component\DataSource\Driver\Collection\Exception\CollectionDriverException;
use FSi\Component\DataSource\Field\FieldAbstractType;
use FSi\Component\DataSource\Field\FieldInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_shift;
use function in_array;
use function is_array;
use function settype;

abstract class CollectionAbstractField extends FieldAbstractType implements CollectionFieldInterface
{
    public function initOptions(OptionsResolver $optionsResolver): void
    {
        parent::initOptions($optionsResolver);

        $optionsResolver
            ->setDefault('field', null)
            ->setAllowedTypes('field', ['string', 'null'])
            ->setNormalizer('field', function (Options $options, ?string $value): ?string {
                return $value ?? $options['name'];
            })
        ;
    }

    public function getPHPType(): ?string
    {
        return null;
    }

    public function buildCriteria(Criteria $c, FieldInterface $field): void
    {
        if (false === $field->getType() instanceof self) {
            throw new CollectionDriverException(
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

        $type = $this->getPHPType();
        $fieldName = $field->getOption('field');
        $comparison = $field->getOption('comparison');
        $expr = Criteria::expr();

        if ('between' === $comparison) {
            if (false === is_array($data)) {
                throw new CollectionDriverException('Fields with \'between\' comparison require to bind an array.');
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
                if (null !== $type) {
                    settype($from, $type);
                    settype($to, $type);
                }
                $c->andWhere($expr->andX($expr->lte($fieldName, $to), $expr->gte($fieldName, $from)));
                return;
            }
        }

        if (true === in_array($comparison, ['in', 'notIn'], true) && false === is_array($data)) {
            throw new CollectionDriverException(
                'Fields with \'in\' and \'notIn\' comparisons require to bind an array.'
            );
        }

        if (null !== $type) {
            settype($data, $type);
        }
        $c->andWhere($expr->$comparison($fieldName, $data));
    }
}
