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
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Component\DataGrid\Fixtures\SimpleDataGridExtension;

class BooleanTest extends TestCase
{
    private DataGridFactoryInterface $dataGridFactory;

    public function testValues(): void
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Boolean::class, 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
        ]);

        $trueCellView = $this->dataGridFactory->createCellView($column, 1, (object) ['available' => true]);
        $falseCellView = $this->dataGridFactory->createCellView($column, 2, (object) ['available' => false]);

        $this->assertSame('true', $trueCellView->getValue());
        $this->assertSame('false', $falseCellView->getValue());
    }

    public function testAllTrueValues(): void
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Boolean::class, 'available', [
            'true_value' => 'true',
            'field_mapping' => ['available', 'active'],
        ]);

        $cellView = $this->dataGridFactory->createCellView($column, 1, (object) [
            'available' => true,
            'active' => true
        ]);

        $this->assertSame('true', $cellView->getValue());
    }

    public function testMixedValues(): void
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Boolean::class, 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active', 'createdAt'],
        ]);

        $cellView = $this->dataGridFactory->createCellView($column, 1, (object) [
            'available' => true,
            'active' => 1,
            'createdAt' => new \DateTime(),
        ]);

        $this->assertSame('true', $cellView->getValue());
    }

    public function testAllFalseValues(): void
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Boolean::class, 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active'],
        ]);

        $cellView = $this->dataGridFactory->createCellView($column, 1, (object) [
            'available' => false,
            'active' => false,
        ]);

        $this->assertSame('false', $cellView->getValue());
    }

    public function testMixedValuesAndFalse(): void
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Boolean::class, 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active', 'createdAt', 'disabled'],
        ]);

        $cellView = $this->dataGridFactory->createCellView($column, 1, (object) [
            'available' => true,
            'active' => 1,
            'createdAt' => new \DateTime(),
            'disabled' => false,
        ]);

        $this->assertSame('false', $cellView->getValue());
    }

    public function testMixedValuesAndNull(): void
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Boolean::class, 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active', 'createdAt', 'disabled'],
        ]);

        $cellView = $this->dataGridFactory->createCellView($column, 1, (object) [
            'available' => true,
            'active' => 1,
            'createdAt' => new \DateTime(),
            'disabled' => null,
        ]);

        $this->assertSame('true', $cellView->getValue());
    }

    public function testAllNulls(): void
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Boolean::class, 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active'],
        ]);

        $cellView = $this->dataGridFactory->createCellView($column, 1, (object) [
            'available' => null,
            'active' => null,
        ]);

        $this->assertSame('', $cellView->getValue());
    }

    protected function setUp(): void
    {
        $this->dataGridFactory = new DataGridFactory(
            [new SimpleDataGridExtension(new DefaultColumnOptionsExtension(), new Boolean())],
            $this->createMock(DataMapperInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );
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
