<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Doctrine\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Exception\DataGridException;
use FSi\Component\DataGrid\Extension\Doctrine\ColumnTypeExtension\ValueFormatColumnOptionsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValueFormatColumnOptionsExtensionTest extends TestCase
{
    private ValueFormatColumnOptionsExtension $extension;

    public function testGlueAndEmptyValueAsStringWithoutOneValue(): void
    {
        $options = [
            'empty_value' => 'no',
            'value_glue' => ' ',
        ];

        $this->assertFilteredValue($options, [0 => ['id' => null, 'name' => 'Foo']], 'no Foo');
    }

    public function testGlueAndEmptyValueAsStringWithoutValues(): void
    {
        $options = [
            'empty_value' => 'no',
            'value_glue' => ' ',
        ];

        $this->assertFilteredValue($options, [0 => ['id' => null, 'name' => null]], 'no no');
    }

    public function testGlueAndEmptyValueAsArrayWithoutOneValue(): void
    {
        $options = [
            'empty_value' => ['name' => 'no'],
            'value_glue' => ' ',
        ];

        $this->assertFilteredValue($options, [0 => ['id' => 1, 'name' => null]], '1 no');
    }

    public function testGlueAndEmptyValueAsArrayWithoutValues(): void
    {
        $options = [
            'empty_value' => ['id' => 'no', 'name' => 'no'],
            'value_glue' => ' ',
        ];

        $this->assertFilteredValue($options, [0 => ['id' => null, 'name' => null]], 'no no');
    }

    public function testGlueMultipleAndEmptyValueAsArrayWithoutMultipleValues(): void
    {
        $options = [
            'empty_value' => ['id' => 'no', 'name' => 'no'],
            'glue_multiple' => '<br />',
            'value_glue' => ' ',
        ];

        $value = [
            0 => [
                'id' => null,
                'name' => null
            ],
            1 => [
                'id' => null,
                'name' => 'Foo'
            ],
        ];

        $this->assertFilteredValue($options, $value, 'no no<br />no Foo');
    }

    public function testGlueAndEmptyValueAsArrayWithoutKeyInEmptyValue(): void
    {
        $options = [
            'empty_value' => ['id2' => 'no', 'name' => 'no'],
        ];

        $this->expectException(DataGridException::class);
        $this->assertFilteredValue($options, [0 => ['id' => null, 'name' => 'Foo']], 'unreachable');
    }

    public function testMissingFormatAndGlue(): void
    {
        $this->assertFilteredValue([], [0 => ['id' => 1, 'name' => 'Foo']], '1 Foo');
    }

    public function testFormatAndGlue(): void
    {
        $options = [
            'value_format' => '(%s)',
            'value_glue' => '<br />',
        ];

        $this->assertFilteredValue($options, [0 => ['id' => 1, 'name' => 'Foo']], '(1)<br />(Foo)');
    }

    public function testFormatWithTooManyPlaceholders(): void
    {
        $options = [
            'value_format' => '(%s) (%s)',
            'value_glue' => '<br />',
        ];

        $this->expectError();
        $this->assertFilteredValue($options, [0 => ['id' => 1, 'name' => 'Foo']], 'unreachable');
    }

    public function testFormatGlueAndGlueMultiple()
    {
        $options = [
            'glue_multiple' => '<br />',
            'value_format' => '(%s)',
            'value_glue' => ' ',
        ];

        $value = [
            0 => [
                'id' => 1,
                'name' => 'Foo',
            ],
            1 => [
                'id' => 2,
                'name' => 'Bar',
            ],
        ];

        $this->assertFilteredValue($options, $value, '(1) (Foo)<br />(2) (Bar)');
    }

    protected function setUp(): void
    {
        $this->extension = new ValueFormatColumnOptionsExtension();
    }

    private function assertFilteredValue(array $options, $value, $filteredValue): void
    {
        $column = $this->createMock(ColumnInterface::class);

        $optionsResolver = new OptionsResolver();
        $this->extension->initOptions($optionsResolver);
        $options = $optionsResolver->resolve($options);

            $column->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function (string $option) use ($options) {
                if (true === array_key_exists($option, $options)) {
                    return $options[$option];
                }

                return null;
            }));

        $this->assertSame($filteredValue, $this->extension->filterValue($column, $value));
    }
}
