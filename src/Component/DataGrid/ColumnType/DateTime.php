<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\ColumnType;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTime extends ColumnAbstractType
{
    public function getId(): string
    {
        return 'datetime';
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'datetime_format' => 'Y-m-d H:i:s',
            'input_type' => null,
            'input_field_format' => null
        ]);

        $optionsResolver->setAllowedTypes('input_field_format', ['null', 'array', 'string']);

        $optionsResolver->setAllowedValues('input_type', [
            null,
            'string',
            'timestamp',
            'datetime',
            'array'
        ]);
    }

    protected function filterValue(ColumnInterface $column, $value)
    {
        $format = $column->getOption('datetime_format');
        $inputValues = $this->getInputData($column, $value);

        $return = [];
        foreach ($inputValues as $field => $fieldValue) {
            if (empty($fieldValue)) {
                $return[$field]  = null;
                continue;
            }

            if (true === is_string($format)) {
                $return[$field] = $fieldValue->format($format);
                continue;
            }

            if (true === is_array($format)) {
                if (false === array_key_exists($field, $format)) {
                    throw new DataGridColumnException(
                        sprintf('There is not format for field "%s" in "format" option value.', $field)
                    );
                }

                $return[$field] = $fieldValue->format($format[$field]);
            }
        }

        return $return;
    }

    /**
     * @param ColumnInterface $column
     * @param array<string,mixed> $value
     * @return array<string,mixed>
     */
    private function getInputData(ColumnInterface $column, array $value)
    {
        $inputType = $column->getOption('input_type');
        /** @var array<string,string|array<string>>|string|null $mappingFormat */
        $mappingFormat = $column->getOption('input_field_format');

        if (null === $inputType) {
            $inputType = $this->guessInputType($value);
        }

        /** @var array<int,string> $mappingFields */
        $mappingFields = $column->getOption('field_mapping');
        $inputData = [];
        foreach ($mappingFields as $field) {
            $inputData[$field] = null;
        }
        if (null === $inputType) {
            return $inputData;
        }

        switch (strtolower($inputType)) {
            case 'array':
                if (null === $mappingFormat) {
                    throw new DataGridColumnException(
                        '"input_field_format" option is missing. Example: '
                            . '"input_field_format" => array("mapping_field_name" => array("input" => "datetime"))'
                    );
                }
                if (false === is_array($mappingFormat)) {
                    throw new DataGridColumnException(
                        '"input_field_format" option value must be an array with keys that match mapping fields names.'
                    );
                }
                if (count($mappingFormat) !== count($value)) {
                    throw new DataGridColumnException(
                        '"input_field_format" option value array must have same count as "field_mapping" option'
                            . ' value array.  '
                    );
                }

                foreach ($mappingFormat as $field => $inputType) {
                    if (false === is_string($field) || false === array_key_exists($field, $value)) {
                        throw new DataGridColumnException(
                            sprintf('Unknown mapping field "%s".', $field)
                        );
                    }
                    if (false === is_array($inputType)) {
                        throw new DataGridColumnException(
                            sprintf('"%s" should be an array.', $field)
                        );
                    }
                    $fieldInputType = $inputType['input_type'] ?? $this->guessInputType($value[$field]);

                    switch (strtolower($fieldInputType)) {
                        case 'string':
                            $mappingFormat = $inputType['datetime_format'] ?? null;
                            if (null === $mappingFormat) {
                                throw new DataGridColumnException(
                                    sprintf(
                                        '"datetime_format" option is required in "input_field_format" for field "%s".',
                                        $field
                                    )
                                );
                            }
                            if (empty($value[$field])) {
                                $inputData[$field] = null;
                            } else {
                                $inputData[$field] = $this->transformStringToDateTime($value[$field], $mappingFormat);
                            }
                            break;

                        case 'timestamp':
                            if (empty($value[$field])) {
                                $inputData[$field] = null;
                            } else {
                                $inputData[$field] = $this->transformTimestampToDateTime($value[$field]);
                            }
                            break;

                        case 'datetime':
                            if (!empty($value[$field]) && (false === $value[$field] instanceof DateTimeInterface)) {
                                throw new DataGridColumnException(
                                    sprintf(
                                        'Value in field "%s" is "%s" type instead of "\DateTimeInterface" instance.',
                                        $field,
                                        gettype($value[$field])
                                    )
                                );
                            }

                            $inputData[$field] = $value[$field];
                            break;

                        default:
                            throw new DataGridColumnException(sprintf(
                                '"%s" is not valid input option value for field "%s". '
                                    . 'You should consider using one of "array", "string", "datetime" or "timestamp" '
                                    . ' input option values. ',
                                $fieldInputType,
                                $field
                            ));
                    }
                }
                break;

            case 'string':
                $field = key($value);
                $value = current($value);

                if (!empty($value) && false === is_string($value)) {
                    throw new DataGridColumnException(
                        sprintf('Value in field "%s" is not a valid string.', $field)
                    );
                }

                if (empty($value)) {
                    $inputData[$field] = null;
                } else {
                    $inputData[$field] = $this->transformStringToDateTime($value, $mappingFormat);
                }

                break;

            case 'datetime':
                $field = key($value);
                $value = current($value);

                if (!empty($value) && (false === $value instanceof DateTimeInterface)) {
                    throw new DataGridColumnException(
                        sprintf('Value in field "%s" is not instance of "\DateTimeInterface"', $field)
                    );
                }

                $inputData[$field] = $value;
                break;

            case 'timestamp':
                $field = key($value);
                $value = current($value);

                if (empty($value)) {
                    $inputData[$field] = null;
                } else {
                    $inputData[$field] = $this->transformTimestampToDateTime($value);
                }
                break;

            default:
                throw new DataGridColumnException(
                    sprintf(
                        '"%s" is not valid input option value. '
                            . 'You should consider using one of "array", "string", "datetime" or "timestamp" input '
                            . 'option values. ',
                        $inputType
                    )
                );
        }

        return $inputData;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function guessInputType($value): ?string
    {
        if (true === is_array($value)) {
            if (count($value) > 1) {
                throw new DataGridColumnException(
                    'If you want to use more that one mapping fields you need to set "input" option value "array".'
                );
            }
            $value = current($value);
        }

        if (true === $value instanceof DateTimeInterface) {
            return 'datetime';
        }

        if (true === is_numeric($value)) {
            return 'timestamp';
        }

        if (true === is_string($value)) {
            return 'string';
        }

        return null;
    }

    /**
     * @param string $value
     * @param mixed $mappingFormat
     * @return DateTimeImmutable
     */
    private function transformStringToDateTime(string $value, $mappingFormat): DateTimeImmutable
    {
        if (null === $mappingFormat) {
            throw new DataGridColumnException(
                '"mapping_fields_format" option is missing. Example: "mapping_fields_format" => "Y-m-d H:i:s"'
            );
        }

        if (false === is_string($mappingFormat)) {
            throw new DataGridColumnException(
                'When using input type "string", "mapping_fields_format" option must be an string that contains '
                    . 'valid data format'
            );
        }

        $dateTime = DateTimeImmutable::createFromFormat($mappingFormat, $value);
        if (false === $dateTime instanceof DateTimeInterface) {
            throw new DataGridColumnException(
                sprintf('value "%s" does not fit into format "%s" ', $value, $mappingFormat)
            );
        }

        return $dateTime;
    }

    /**
     * @param int|string $value
     * @return DateTimeImmutable
     */
    private function transformTimestampToDateTime($value): DateTimeImmutable
    {
        if (false === is_numeric($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Value in should be timestamp but "%s" type was detected.'
                        . ' Maybe you should consider using different "input" option value?',
                    gettype($value)
                )
            );
        }

        $dateTime = new DateTimeImmutable();

        return $dateTime->setTimestamp((int) $value);
    }
}
