<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Extension\Core\ColumnType;

use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function is_iterable;

class Entity extends ColumnAbstractType
{
    public function getId(): string
    {
        return 'entity';
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'relation_field' => static fn(Options $options, $previousValue) => $previousValue ?? $options['name'],
        ]);

        $optionsResolver->setAllowedTypes('relation_field', 'string');
    }

    protected function getValue(ColumnInterface $column, $object)
    {
        /** @var string $relationField */
        $relationField = $column->getOption('relation_field');

        $values = $column->getDataGrid()->getDataMapper()->getData($relationField, $object);
        $value = $this->filterValue($column, $values);
        foreach ($this->columnTypeExtensions as $extension) {
            $value = $extension->filterValue($column, $value);
        }

        return $value;
    }

    protected function filterValue(ColumnInterface $column, $value)
    {
        $values = [];
        $objectValues = [];
        /** @var array<string> $mappingFields */
        $mappingFields = $column->getOption('field_mapping');

        if (true === is_iterable($value)) {
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
}
