<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Extension\Doctrine\ColumnType;

use Doctrine\Common\Collections\Collection;
use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Entity extends ColumnAbstractType
{
    public function getId(): string
    {
        return 'entity';
    }

    public function getValue(ColumnInterface $column, $object)
    {
        /** @var string $relationField */
        $relationField = $column->getOption('relation_field');

        return $column->getDataGrid()->getDataMapper()->getData($relationField, $object);
    }

    public function filterValue(ColumnInterface $column, $value)
    {
        if (true === $value instanceof Collection) {
            $value = $value->toArray();
        }

        $values = [];
        $objectValues = [];
        /** @var array $mappingFields */
        $mappingFields = $column->getOption('field_mapping');

        if (true === is_array($value)) {
            foreach ($value as $object) {
                foreach ($mappingFields as $field) {
                    $objectValues[$field] = $column->getDataGrid()->getDataMapper()->getData($field, $object);
                }

                $values[] = $objectValues;
            }
        } else {
            foreach ($mappingFields as $field) {
                $objectValues[$field] = null !== $value
                    ? $column->getDataGrid()->getDataMapper()->getData($field, $value)
                    : null;
            }

            $values[] = $objectValues;
        }

        return $values;
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'relation_field' => function (Options $options, $previousValue) {
                return $previousValue ?? $options['name'];
            },
        ]);

        $optionsResolver->setAllowedTypes('relation_field', 'string');
    }
}
