<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\ColumnType;

use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function in_array;
use function number_format;
use function round;

use const PHP_ROUND_HALF_UP;
use const PHP_ROUND_HALF_DOWN;
use const PHP_ROUND_HALF_EVEN;
use const PHP_ROUND_HALF_ODD;

class Money extends ColumnAbstractType
{
    public const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    public const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    public const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    public const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;

    public function getId(): string
    {
        return 'money';
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'round_mode' => self::ROUND_HALF_UP,
            'precision' => 2,
            'decimals' => 2,
            'dec_point' => '.',
            'thousands_sep' => ',',
            'value_currency_separator' => ' ',
            'currency' => null,
            'currency_field' => null,
        ]);

        $optionsResolver->setAllowedTypes('round_mode', 'integer');
        $optionsResolver->setAllowedTypes('precision', 'integer');
        $optionsResolver->setAllowedTypes('decimals', 'integer');
        $optionsResolver->setAllowedTypes('decimals', 'integer');
        $optionsResolver->setAllowedTypes('dec_point', 'string');
        $optionsResolver->setAllowedTypes('thousands_sep', 'string');
        $optionsResolver->setAllowedTypes('value_currency_separator', 'string');
        $optionsResolver->setAllowedTypes('currency', ['null', 'string']);
        $optionsResolver->setAllowedTypes('currency_field', ['null', 'string']);
    }

    protected function filterValue(ColumnInterface $column, $value)
    {
        $precision = $column->getOption('precision');
        $roundmode = $column->getOption('round_mode');
        $decimals = $column->getOption('decimals');
        $decPoint = $column->getOption('dec_point');
        $thousands = $column->getOption('thousands_sep');
        $currencyField = $column->getOption('currency_field');
        $currencyValue = $column->getOption('currency');
        $mappingFields = $column->getOption('field_mapping');
        $currencySeparator = $column->getOption('value_currency_separator');

        if (null === $currencyField && null === $currencyValue) {
            throw new DataGridColumnException(sprintf(
                'At least one option from "currency" and "currency_field" must be defined in "%s" field.',
                $column->getName()
            ));
        }

        $currency = $currencyValue;
        if (null !== $currencyField) {
            if (false === in_array($currencyField, $mappingFields, true)) {
                throw new DataGridColumnException(
                    "There is no field with name \"{$currencyField}\"."
                );
            }

            $currency = $value[$currencyField];
            unset($value[$currencyField]);
        }

        foreach ($value as &$val) {
            if (true === empty($val)) {
                continue;
            }

            $val = round($val, $precision, $roundmode);
            $val = number_format($val, $decimals, $decPoint, $thousands);

            $val .= $currencySeparator . $currency;
        }

        return $value;
    }
}
