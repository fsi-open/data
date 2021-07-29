<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Extension\Doctrine\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\Exception\DataGridException;
use FSi\Component\DataGrid\Extension\Doctrine\ColumnType\Entity;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;

class ValueFormatColumnOptionsExtension extends ColumnAbstractTypeExtension
{
    public function filterValue(ColumnInterface $column, $value)
    {
        $resultValue = [];
        /** @var array|string|null $emptyValue */
        $emptyValue = $column->getOption('empty_value');
        if (null !== $emptyValue) {
            $value = $this->populateValues($value, $emptyValue);
        }
        /** @var string|null $glue */
        $glue = $column->getOption('value_glue');
        /** @var string|null $format */
        $format = $column->getOption('value_format');

        foreach ($value as $val) {
            $objectValue = null;

            if (null !== $glue && null === $format) {
                $objectValue = implode($glue, $val);
            }

            if (null !== $format) {
                if (null !== $glue) {
                    $formattedValues = [];
                    foreach ($val as $fieldValue) {
                        $formattedValues[] = sprintf($format, $fieldValue);
                    }

                    $objectValue = implode($glue, $formattedValues);
                } else {
                    $objectValue = vsprintf($format, $val);
                }
            }

            $resultValue[] = $objectValue;
        }

        return implode($column->getOption('glue_multiple'), $resultValue);
    }

    public function getExtendedColumnTypes(): array
    {
        return [
            Entity::class,
        ];
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'glue_multiple' => ' ',
            'value_glue' => ' ',
            'value_format' => '%s',
            'empty_value' => null
        ]);

        $optionsResolver->setAllowedTypes('glue_multiple', ['string']);
        $optionsResolver->setAllowedTypes('value_glue', ['string', 'null']);
        $optionsResolver->setAllowedTypes('value_format', ['string', 'null']);
        $optionsResolver->setAllowedTypes('empty_value', ['array', 'string', 'null']);
    }

    private function populateValues(array $values, $emptyValue): array
    {
        foreach ($values as &$val) {
            foreach ($val as $fieldKey => &$fieldValue) {
                if (null === $fieldValue) {
                    $fieldValue = $this->populateValue($fieldKey, $fieldValue, $emptyValue);
                }
            }
        }

        return $values;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param mixed $emptyValue
     * @return mixed
     */
    private function populateValue(string $key, $value, $emptyValue)
    {
        if (true === is_string($emptyValue)) {
            $value = $emptyValue;
        } elseif (true === is_array($emptyValue)) {
            if (true === array_key_exists($key, $emptyValue)) {
                $value = $emptyValue[$key];
            } else {
                throw new DataGridException(
                    sprintf('Not found key "%s" in empty_value array', $key)
                );
            }
        }

        return $value;
    }
}
