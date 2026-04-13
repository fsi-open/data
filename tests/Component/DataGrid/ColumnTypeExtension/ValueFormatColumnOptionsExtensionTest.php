<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\ColumnType\Text;
use FSi\Component\DataGrid\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\ColumnTypeExtension\ValueFormatColumnOptionsExtension;
use FSi\Component\DataGrid\ColumnTypeExtension\ValueFormatter;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use ValueError;

use const PHP_VERSION_ID;

final class ValueFormatColumnOptionsExtensionTest extends TestCase
{
    private ValueFormatColumnOptionsExtension $extension;

    public function testValueGlueOption(): void
    {
        $options = [
            'empty_value' => '',
            'field_mapping' => [],
            'value_glue' => '-',
        ];

        $this->assertFilteredValue($options, ['foo', 'bar'], 'foo-bar');
    }

    public function testEmptyFormatOptions(): void
    {
        $options = [
            'empty_value' => '',
            'field_mapping' => [],
            'value_glue' => null,
            'value_format' => null,
        ];

        $this->assertFilteredValue($options, ['foo'], 'foo');
    }

    public function testFormatAndGlueOptions(): void
    {
        $options = [
            'value_format' => '<b>%s</b>',
            'value_glue' => '<br/>',
            'empty_value' => '',
            'field_mapping' => [],
        ];

        $this->assertFilteredValue($options, ['foo', 'bar'], '<b>foo</b><br/><b>bar</b>');
    }

    public function testEmptyFormatAndGlueWithArrayValue(): void
    {
        $optionsResolver = $this->createOptionsResolver();
        $this->extension->initOptions($optionsResolver);

        $options = [
            'value_format' => null,
            'value_glue' => null,
            'empty_value' => '',
            'field_mapping' => ['value', 'another_value'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of "value_format" or "value_glue" option is missing.');

        $optionsResolver->resolve($options);
    }

    public function testTemplate(): void
    {
        $options = [
            'value_format' => '<b>%s</b>',
            'value_glue' => '',
            'empty_value' => '',
            'field_mapping' => [],
        ];

        $this->assertFilteredValue($options, ['foo'], '<b>foo</b>');
    }

    public function testFormatWithoutGlueWithArrayValue(): void
    {
        $options = [
            'value_format' => '<b>%s</b><br/><b>%s</b>',
            'value_glue' => null,
            'empty_value' => '',
            'field_mapping' => [],
        ];

        $this->assertFilteredValue($options, ['foo', 'bar'], '<b>foo</b><br/><b>bar</b>');
    }

    public function testFormatThatHaveTooManyPlaceholders(): void
    {
        $options = [
            'value_format' => '%s%s',
            'value_glue' => null,
            'empty_value' => '',
            'field_mapping' => [],
        ];

        $this->expectException(ValueError::class);
        $this->assertFilteredValue($options, ['foo'], 'unreachable');
    }

    public function testFormatThatHaveNotEnoughPlaceholders(): void
    {
        $options = [
            'value_format' => '<b>%s</b>',
            'value_glue' => null,
            'empty_value' => '',
            'field_mapping' => [],
        ];

        $this->assertFilteredValue($options, ['foo', 'bar'], '<b>foo</b>');
    }

    public function testEmptyTemplate(): void
    {
        $options = [
            'empty_value' => '',
            'value_format' => '',
            'field_mapping' => [],
        ];

        $this->assertFilteredValue($options, ['foo', 'bar'], '');
    }

    public function testEmptyValue(): void
    {
        $options = [
            'empty_value' => 'empty',
            'field_mapping' => [],
        ];

        $this->assertFilteredValue($options, [null], 'empty');
    }

    public function testSingleEmptyValueWithArrayValue(): void
    {
        $options = [
            'empty_value' => 'empty',
            'value_glue' => ' ',
            'field_mapping' => [],
        ];

        $this->assertFilteredValue($options, ['val', '', null], 'val empty empty');
    }

    public function testClosureFormat(): void
    {
        $options = [
            'empty_value' => '',
            'value_format' => static fn (array $data): string => $data['fo'] . '-' . $data['ba'],
            'field_mapping' => ['fo', 'ba'],
        ];

        $this->assertFilteredValue($options, ['fo' => 'foo', 'ba' => 'bar'], 'foo-bar');
    }

    public function testZeroValue(): void
    {
        $options = [
            'empty_value' => 'should not be used',
            'value_glue' => '',
            'field_mapping' => ['fo'],
        ];

        $this->assertFilteredValue($options, ['fo' => 0], '0');
    }

    public function testFormatClosure(): void
    {
        $columnType = new Text(
            [new DefaultColumnOptionsExtension(), $this->extension],
            new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor())
        );

        $column = $columnType->createColumn($this->createMock(DataGridInterface::class), 'text', [
            'field_mapping' => ['text'],
            'value_format' => static fn(array $data): string => "{$data['text']} {$data['text']}"
        ]);

        $cellView = $columnType->createCellView($column, 1, (object) ['text' => 'bar']);
        $this->assertSame('bar bar', $cellView->getValue());
    }

    protected function setUp(): void
    {
        $this->extension = new ValueFormatColumnOptionsExtension(new ValueFormatter());
    }

    /**
     * @param array<string,mixed> $options
     * @param array<int|string,mixed> $value
     * @param string $filteredValue
     */
    private function assertFilteredValue(array $options, array $value, string $filteredValue): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getOption')->willReturnCallback(
            fn(string $option) => true === array_key_exists($option, $options) ? $options[$option] : null
        );

        $this->assertSame($filteredValue, $this->extension->filterValue($column, $value));
    }

    private function createOptionsResolver(): OptionsResolver
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault(
            'field_mapping',
            static fn(Options $options, $previousValue) => $previousValue ?? [$options['name']]
        );

        $optionsResolver->setAllowedTypes('field_mapping', 'array');
        return $optionsResolver;
    }
}
