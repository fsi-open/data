<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\ColumnType\Boolean;
use FSi\Component\DataGrid\ColumnType\Collection;
use FSi\Component\DataGrid\ColumnType\DateTime;
use FSi\Component\DataGrid\ColumnType\Money;
use FSi\Component\DataGrid\ColumnType\Number;
use FSi\Component\DataGrid\ColumnType\Text;
use InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function count;
use function current;
use function implode;
use function is_array;
use function is_callable;
use function is_string;
use function reset;
use function sprintf;
use function vsprintf;

class ValueFormatColumnOptionsExtension extends ColumnAbstractTypeExtension
{
    public static function getExtendedColumnTypes(): array
    {
        return [
            Text::class,
            Boolean::class,
            DateTime::class,
            Collection::class,
            Number::class,
            Money::class,
        ];
    }

    public function filterValue(ColumnInterface $column, $value)
    {
        $this->validateEmptyValueOption($column);
        $value = $this->populateValue($value, $column->getOption('empty_value'));
        $glue = $column->getOption('value_glue');
        $format = $column->getOption('value_format');

        $value = $this->formatValue($value, $format, $glue);

        if (null === $glue && null === $format && true === is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'At least one of "value_format" or "value_glue" option is missing in column: "%s".',
                $column->getName()
            ));
        }

        return $value;
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'value_glue' => null,
            'value_format' => null,
            'empty_value' => '',
        ]);

        $optionsResolver->setAllowedTypes('value_glue', ['string', 'null']);
        $optionsResolver->setAllowedTypes('value_format', ['string', 'callable', 'null']);
        $optionsResolver->setAllowedTypes('empty_value', 'string');
    }

    private function validateEmptyValueOption(ColumnInterface $column): void
    {
        $emptyValue = $column->getOption('empty_value');
        $mappingFields = $column->getOption('field_mapping');

        if (true === is_string($emptyValue)) {
            return;
        }

        if (false === is_array($emptyValue)) {
            throw new InvalidArgumentException(
                sprintf('Option "empty_value" in column: "%s" must be a array.', $column->getName())
            );
        }

        foreach ($emptyValue as $field => $value) {
            if (false === in_array($field, $mappingFields, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Mapping field "%s" doesn\'t exist in column: "%s".',
                        $field,
                        $column->getName()
                    )
                );
            }

            if (false === is_string($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Option "empty_value" for field "%s" in column: "%s" must be a string.',
                        $field,
                        $column->getName()
                    )
                );
            }
        }
    }

    /**
     * @param mixed $value
     * @param mixed $emptyValue
     * @return array<string,mixed>|string
     */
    private function populateValue($value, $emptyValue)
    {
        if (true === is_string($emptyValue)) {
            if (null === $value || '' === $value) {
                return $emptyValue;
            }

            if (true === is_array($value)) {
                foreach ($value as &$val) {
                    if (null === $val || '' === $val) {
                        $val = $emptyValue;
                    }
                }
            }

            return $value;
        }

        /**
         * If value is simple string and $empty_value is array there is no way
         * to guess which empty_value should be used.
         */
        if (true === is_string($value)) {
            return $value;
        }

        if (true === is_array($value)) {
            foreach ($value as $field => &$fieldValue) {
                if (null === $fieldValue || '' === $fieldValue) {
                    $fieldValue = $emptyValue[$field] ?? '';
                }
            }
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param string|callable|null $format
     * @param string|null $glue
     * @return mixed
     */
    private function formatValue($value, $format = null, ?string $glue = null)
    {
        if (true === is_array($value) && null !== $glue && null === $format) {
            $value = implode($glue, $value);
        }

        if (null !== $format) {
            if (true === is_array($value)) {
                if (null !== $glue) {
                    $renderedValues = [];
                    foreach ($value as $val) {
                        $renderedValues[] = $this->formatSingleValue($val, $format);
                    }

                    $value = implode($glue, $renderedValues);
                } else {
                    $value = $this->formatMultipleValues($value, $format);
                }
            } else {
                $value = $this->formatSingleValue($value, $format);
            }
        }

        if (true === is_array($value) && 1 === count($value)) {
            reset($value);
            $value = current($value);
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param string|callable $template
     * @return string
     */
    private function formatSingleValue($value, $template): string
    {
        if (true === is_callable($template)) {
            return $template($value);
        }

        return sprintf($template, $value);
    }

    /**
     * @param array<string,mixed> $value
     * @param string|callable $template
     * @return string
     */
    private function formatMultipleValues(array $value, $template): string
    {
        if (true === is_callable($template)) {
            return $template($value);
        }

        return vsprintf($template, $value);
    }
}
