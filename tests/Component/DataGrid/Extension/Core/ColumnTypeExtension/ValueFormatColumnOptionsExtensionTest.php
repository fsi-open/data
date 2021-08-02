<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Text;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\ValueFormatColumnOptionsExtension;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ValueFormatColumnOptionsExtensionTest extends TestCase
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
        $options = [
            'value_format' => null,
            'value_glue' => null,
            'empty_value' => '',
            'field_mapping' => [],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->assertFilteredValue($options, ['foo', 'bar'], 'unreachable');
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

        $this->expectError();
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

    public function testArrayEmptyValue(): void
    {
        $options = [
            'empty_value' => [],
            'field_mapping' => [],
        ];

        $this->assertFilteredValue($options, [null], '');
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

    public function testMultipleEmptyValueWithArrayValue(): void
    {
        $options = [
            'empty_value' => [
                'fo' => 'foo',
                'ba' => 'bar'
            ],
            'value_glue' => ' ',
            'field_mapping' => ['fo', 'ba'],
        ];

        $this->assertFilteredValue($options, ['fo' => null, 'ba' => null], 'foo bar');
    }

    public function testEmptyValueThatNotExistsInMappingFields(): void
    {
        $options = [
            'empty_value' => [
                'fo' => 'empty',
            ],
            'field_mapping' => ['fos'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->assertFilteredValue($options, ['unused'], 'unreachable');
    }

    public function testMultipleEmptyValueWithArrayValueAndTemplate(): void
    {
        $options = [
            'empty_value' => [
                'fo' => 'empty-foo',
                'ba' => 'empty-bar'
            ],
            'value_format' => '"%s" "%s" "%s"',
            'field_mapping' => ['fo', 'ba', 'ca'],
        ];

        $this->assertFilteredValue($options, ['fo' => '', 'ba' => '', 'ca' => null], '"empty-foo" "empty-bar" ""');
    }

    public function testClosureFormat(): void
    {
        $options = [
            'empty_value' => [],
            'value_format' => function ($data) {
                return $data['fo'] . '-' . $data['ba'];
            },
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
        $columnType = new Text([new DefaultColumnOptionsExtension(), $this->extension]);

        $column = $columnType->createColumn($this->getDataGridMock(), 'text', [
            'field_mapping' => ['text'],
            'value_format' => function ($data) {
                return sprintf('%s %s', $data['text'], $data['text']);
            }
        ]);
        $cellView = $columnType->createCellView($column, 1, (object) ['text' => 'bar']);

        $this->assertSame('bar bar', $cellView->getValue());
    }

    protected function setUp(): void
    {
        $this->extension = new ValueFormatColumnOptionsExtension();
    }

    /**
     * @return DataGridInterface&MockObject
     */
    private function getDataGridMock(): DataGridInterface
    {
        $dataGrid = $this->createMock(DataGridInterface::class);
        $dataGrid->method('getDataMapper')
            ->willReturn(new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor()));

        return $dataGrid;
    }

    /**
     * @param array<string,mixed> $options
     * @param array<int|string,mixed> $value
     * @param string $filteredValue
     */
    private function assertFilteredValue(array $options, array $value, string $filteredValue): void
    {
        $column = $this->createMock(ColumnInterface::class);

        $column->method('getOption')
            ->willReturnCallback(static function (string $option) use ($options) {
                if (true === array_key_exists($option, $options)) {
                    return $options[$option];
                }

                return null;
            });

        $this->assertSame($filteredValue, $this->extension->filterValue($column, $value));
    }
}
