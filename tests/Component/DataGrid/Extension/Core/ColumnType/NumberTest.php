<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Core\ColumnType;

use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Number;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class NumberTest extends TestCase
{
    private Number $columnType;

    public function testPrecision(): void
    {
        $this->assertCellValue(
            ['precision' => 2, 'round_mode' => Number::ROUND_HALF_UP],
            (object) ['number' => 10.123],
            ['number' => 10.12]
        );
    }

    public function testRoundMode(): void
    {
        $this->assertCellValue(
            ['round_mode' => Number::ROUND_HALF_UP],
            (object) ['number' => 10.126],
            ['number' => 10.13]
        );
    }

    public function testNumberFormat(): void
    {
        $this->assertCellValue(
            [],
            (object) ['number' => 12345678.1],
            ['number' => 12345678.1]
        );

        $this->assertCellValue(
            ['format' => true],
            (object) ['number' => 12345678.1],
            ['number' => '12,345,678.10']
        );

        $this->assertCellValue(
            ['format' => true, 'format_decimals' => 0],
            (object) ['number' => 12345678.1],
            ['number' => '12,345,678']
        );

        $this->assertCellValue(
            ['format' => true, 'format_decimals' => 2],
            (object) ['number' => 12345678.1],
            ['number' => '12,345,678.10']
        );

        $this->assertCellValue(
            ['format' => true, 'format_decimals' => 2, 'format_dec_point' => ',', 'format_thousands_sep' => ' '],
            (object) ['number' => 12345678.1],
            ['number' => '12 345 678,10']
        );

        $this->assertCellValue(
            ['format' => true, 'format_decimals' => 2, 'format_dec_point' => ',', 'format_thousands_sep' => ' '],
            (object) ['number' => 1000],
            ['number' => '1 000,00']
        );

        $this->assertCellValue(
            ['format' => true, 'format_decimals' => 0, 'format_dec_point' => ',', 'format_thousands_sep' => ' '],
            (object) ['number' => 1000],
            ['number' => '1 000']
        );

        $this->assertCellValue(
            ['format' => false, 'format_decimals' => 2, 'format_dec_point' => ',', 'format_thousands_sep' => ' '],
            (object) ['number' => 1000],
            ['number' => 1000]
        );
    }

    protected function setUp(): void
    {
        $this->columnType = new Number([new DefaultColumnOptionsExtension()]);
    }

    /**
     * @param array<string,mixed> $options
     * @param object $value
     * @param array<string,mixed> $expectedValue
     */
    private function assertCellValue(array $options, object $value, array $expectedValue): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'number', $options);
        $cellView = $this->columnType->createCellView($column, 1, $value);

        $this->assertSame($expectedValue, $cellView->getValue());
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
}
