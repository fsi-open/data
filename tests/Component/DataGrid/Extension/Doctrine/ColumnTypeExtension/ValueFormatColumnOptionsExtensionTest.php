<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Doctrine\ColumnTypeExtension;

use ArgumentCountError;
use FSi\Component\DataGrid\Exception\DataGridException;
use FSi\Component\DataGrid\Extension\Doctrine\ColumnTypeExtension\ValueFormatColumnOptionsExtension;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\Column\CellViewInterface;
use PHPUnit\Framework\Error\Error;
use PHPUnit\Framework\TestCase;

use const PHP_VERSION_ID;

class ValueFormatColumnOptionsExtensionTest extends TestCase
{
    public function testBuildCellViewWithGlueAndEmptyValueAsStringAndWithoutOneValue(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => null,
                        'name' => 'Foo'
                    ]
                ]
            );

        $column->expects(self::atLeast(4))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'value_glue':
                        case 'glue_multiple':
                            return ' ';

                        case 'empty_value':
                            return 'no';
                    }

                    return null;
                }
            );

        $view->expects(self::once())
            ->method('setValue')
            ->with('no Foo');

        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithGlueAndEmptyValueAsStringAndWithoutValues(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => null,
                        'name' => null
                    ]
                ]
            );

        $column->expects(self::atLeast(4))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'value_glue':
                        case 'glue_multiple':
                            return ' ';

                        case 'empty_value':
                            return 'no';
                    }

                    return null;
                }
            );

        $view->expects(self::once())
            ->method('setValue')
            ->with('no no');

        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithGlueAndEmptyValueAsArrayAndWithoutOneValue(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => 1,
                        'name' => null
                    ]
                ]
            );

        $column->expects(self::atLeast(4))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'value_glue':
                        case 'glue_multiple':
                            return ' ';

                        case 'empty_value':
                            return ['name' => 'no'];
                    }

                    return null;
                }
            );

        $view->expects(self::once())
            ->method('setValue')
            ->with('1 no');

        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithGlueAndEmptyValueAsArrayAndWithoutValues(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => null,
                        'name' => null
                    ]
                ]
            );

        $column->expects(self::atLeast(4))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'value_glue':
                        case 'glue_multiple':
                            return ' ';

                        case 'empty_value':
                            return ['id' => 'no', 'name' => 'no'];
                    }

                    return null;
                }
            );

        $view->expects(self::once())
            ->method('setValue')
            ->with('no no');

        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithGlueAndGlueMultipleAndEmptyValueAsArrayAndWithoutMultipleValues(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => null,
                        'name' => null
                    ],
                    1 => [
                        'id' => null,
                        'name' => 'Foo'
                    ]
                ]
            );

        $column->expects(self::atLeast(4))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'value_glue':
                            return ' ';

                        case 'glue_multiple':
                            return '<br />';

                        case 'empty_value':
                            return ['id' => 'no', 'name' => 'no'];
                    }

                    return null;
                }
            );

        $view->expects(self::once())
            ->method('setValue')
            ->with('no no<br />no Foo');

        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithGlueAndEmptyValueAsArrayAndNotFoundKeyInEmptyValue(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => null,
                        'name' => 'Foo'
                    ]
                ]
            );

        $column->expects(self::atLeastOnce())
            ->method('getOption')
            ->with('empty_value')
            ->willReturn(['id2' => 'no', 'name' => 'no']);

        $this->expectException(DataGridException::class);
        $this->expectExceptionMessage('Not found key "id" in empty_value array');
        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithoutFormatAndGlue(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => 1,
                        'name' => 'Foo'
                    ]
                ]
            );

        $column->expects(self::atLeast(4))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'glue_multiple':
                            return ' ';
                    }

                    return null;
                }
            );

        $view->expects(self::once())
            ->method('setValue')
            ->with('');

        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithFormatAndGlue(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
        ->method('getValue')
        ->willReturn(
            [
                0 => [
                    'id' => 1,
                    'name' => 'Foo'
                ]
            ]
        );

        $column->expects(self::atLeast(4))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'value_format':
                            return '(%s)';

                        case 'value_glue':
                            return '<br/>';

                        case 'glue_multiple':
                            return ' ';
                    }

                    return null;
                }
            );

        $view->expects(self::once())
            ->method('setValue')
            ->with('(1)<br/>(Foo)');

        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithFormatAndGlueWithToManyPlaceholders(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
        ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => 1,
                        'name' => 'Foo'
                    ]
                ]
            );

        $column->expects(self::atLeast(3))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'value_format':
                            return '(%s) (%s)';

                        case 'value_glue':
                            return '<br/>';
                    }

                    return null;
                }
            );

        $this->expectError();

        $extension->buildCellView($column, $view);
    }

    public function testBuildCellViewWithFormatGlueAndGlueMultiple(): void
    {
        $extension = new ValueFormatColumnOptionsExtension();
        $view = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnTypeInterface::class);

        $view->expects(self::atLeastOnce())
            ->method('getValue')
            ->willReturn(
                [
                    0 => [
                        'id' => 1,
                        'name' => 'Foo',
                    ],
                    1 => [
                        'id' => 2,
                        'name' => 'Bar',
                    ]
                ]
            );

        $column->expects(self::atLeast(4))
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'glue_multiple':
                            return '<br>';

                        case 'value_format':
                            return '(%s)';

                        case 'value_glue':
                            return ' ';
                    }

                    return null;
                }
            );

        $view->expects(self::once())
            ->method('setValue')
            ->with('(1) (Foo)<br>(2) (Bar)');

        $extension->buildCellView($column, $view);
    }
}
