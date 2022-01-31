<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\ColumnType;

use DateTime;
use FSi\Component\DataGrid\ColumnType\Boolean;
use FSi\Component\DataGrid\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class BooleanTest extends TestCase
{
    private Boolean $columnType;

    public function testValues(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
        ]);

        $trueCellView = $this->columnType->createCellView($column, 1, (object) ['available' => true]);
        $falseCellView = $this->columnType->createCellView($column, 2, (object) ['available' => false]);

        $this->assertSame('true', $trueCellView->getValue());
        $this->assertSame('false', $falseCellView->getValue());
    }

    public function testAllTrueValues(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'available', [
            'true_value' => 'true',
            'field_mapping' => ['available', 'active'],
        ]);

        $cellView = $this->columnType->createCellView($column, 1, (object) [
            'available' => true,
            'active' => true
        ]);

        $this->assertSame('true', $cellView->getValue());
    }

    public function testMixedValues(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active', 'createdAt'],
        ]);

        $cellView = $this->columnType->createCellView($column, 1, (object) [
            'available' => true,
            'active' => 1,
            'createdAt' => new DateTime(),
        ]);

        $this->assertSame('true', $cellView->getValue());
    }

    public function testAllFalseValues(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active'],
        ]);

        $cellView = $this->columnType->createCellView($column, 1, (object) [
            'available' => false,
            'active' => false,
        ]);

        $this->assertSame('false', $cellView->getValue());
    }

    public function testMixedValuesAndFalse(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active', 'createdAt', 'disabled'],
        ]);

        $cellView = $this->columnType->createCellView($column, 1, (object) [
            'available' => true,
            'active' => 1,
            'createdAt' => new DateTime(),
            'disabled' => false,
        ]);

        $this->assertSame('false', $cellView->getValue());
    }

    public function testMixedValuesAndNull(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active', 'createdAt', 'disabled'],
        ]);

        $cellView = $this->columnType->createCellView($column, 1, (object) [
            'available' => true,
            'active' => 1,
            'createdAt' => new DateTime(),
            'disabled' => null,
        ]);

        $this->assertSame('true', $cellView->getValue());
    }

    public function testAllNulls(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'available', [
            'true_value' => 'true',
            'false_value' => 'false',
            'field_mapping' => ['available', 'active'],
        ]);

        $cellView = $this->columnType->createCellView($column, 1, (object) [
            'available' => null,
            'active' => null,
        ]);

        $this->assertSame('', $cellView->getValue());
    }

    protected function setUp(): void
    {
        $this->columnType = new Boolean(
            [new DefaultColumnOptionsExtension()],
            new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor())
        );
    }

    /**
     * @return DataGridInterface&MockObject
     */
    private function getDataGridMock(): MockObject
    {
        return $this->createMock(DataGridInterface::class);
    }
}
