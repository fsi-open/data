<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Core\ColumnType;

use FSi\Component\DataGrid\DataGridFactory;
use FSi\Component\DataGrid\DataGridFactoryInterface;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Money;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Component\DataGrid\Fixtures\SimpleDataGridExtension;

class MoneyTest extends TestCase
{
    private DataGridFactoryInterface $dataGridFactory;

    public function testCurrencyOption(): void
    {
        $this->assertCellValue(['currency' => 'EUR'], (object) ['price' => 10], ['price' => '10.00 EUR']);
    }

    public function testCurrencySeparatorOption(): void
    {
        $this->assertCellValue(['value_currency_separator' => '$'], (object) ['price' => 10], ['price' => '10.00$PLN']);
    }

    public function testDecPointOption(): void
    {
        $this->assertCellValue(['dec_point' => '-'], (object) ['price' => 10], ['price' => '10-00 PLN']);
    }

    public function testDecimalsOption(): void
    {
        $this->assertCellValue(['decimals' => 0], (object) ['price' => 10], ['price' => '10 PLN']);

        $this->assertCellValue(['decimals' => 5], (object) ['price' => 10], ['price' => '10.00000 PLN']);
    }

    public function testPrecisionOption(): void
    {
        $this->assertCellValue(['precision' => 2], (object) ['price' => 10.326], ['price' => '10.33 PLN']);

        $this->assertCellValue(['precision' => 2], (object) ['price' => 10.324], ['price' => '10.32 PLN']);
    }

    public function testThousandsSepOption(): void
    {
        $this->assertCellValue(['thousands_sep' => '.'], (object) ['price' => 10000], ['price' => '10.000.00 PLN']);
    }

    protected function setUp(): void
    {
        $this->dataGridFactory = new DataGridFactory(
            [new SimpleDataGridExtension(new DefaultColumnOptionsExtension(), new Money())],
            $this->createMock(DataMapperInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );
    }

    /**
     * @param array<string,mixed> $options
     * @param object $value
     * @param array<string,string> $expectedValue
     */
    private function assertCellValue(array $options, object $value, array $expectedValue): void
    {
        $options = array_merge([
            'currency' => 'PLN',
        ], $options);

        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Money::class, 'price', $options);
        $cellView = $this->dataGridFactory->createCellView($column, 1, $value);

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
