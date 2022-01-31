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
use Symfony\Component\OptionsResolver\OptionsResolver;

use function number_format;
use function round;

use const PHP_ROUND_HALF_UP;
use const PHP_ROUND_HALF_DOWN;
use const PHP_ROUND_HALF_EVEN;
use const PHP_ROUND_HALF_ODD;

class Number extends ColumnAbstractType
{
    public const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    public const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    public const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    public const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;

    public function getId(): string
    {
        return 'number';
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $defaults = [
            'round_mode' => null,
            'precision' => 2,
            'format' => false,
            'format_decimals' => 2,
            'format_dec_point' => '.',
            'format_thousands_sep' => ',',
        ];

        $optionsResolver->setDefaults($defaults);

        $optionsResolver->setAllowedTypes('precision', 'integer');
        $optionsResolver->setAllowedTypes('format', 'bool');
        $optionsResolver->setAllowedTypes('format_decimals', 'integer');
        $optionsResolver->setAllowedTypes('format_dec_point', 'string');
        $optionsResolver->setAllowedTypes('format_thousands_sep', 'string');

        $optionsResolver->setAllowedValues('round_mode', [
            null,
            self::ROUND_HALF_UP,
            self::ROUND_HALF_DOWN,
            self::ROUND_HALF_EVEN,
            self::ROUND_HALF_ODD,
        ]);
    }

    protected function filterValue(ColumnInterface $column, $value)
    {
        $precision = (int) $column->getOption('precision');
        $roundMode = $column->getOption('round_mode');

        /** @var bool $format */
        $format = $column->getOption('format');
        /** @var int $formatDecimals */
        $formatDecimals = $column->getOption('format_decimals');
        /** @var string $formatDecimalPoint */
        $formatDecimalPoint = $column->getOption('format_dec_point');
        /** @var string $formatThousandsSeparator */
        $formatThousandsSeparator = $column->getOption('format_thousands_sep');

        foreach ($value as &$val) {
            if (true === empty($val)) {
                continue;
            }

            if (null !== $roundMode) {
                $val = round($val, $precision, $roundMode);
            }

            if (true === $format) {
                $val = number_format(
                    (float) $val,
                    $formatDecimals,
                    $formatDecimalPoint,
                    $formatThousandsSeparator
                );
            }
        }

        return $value;
    }
}
