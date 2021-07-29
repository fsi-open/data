<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\DataGridFactoryInterface;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataGridView;
use FSi\Component\DataGrid\Data\DataRowsetInterface;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use PHPUnit\Framework\TestCase;

class DataGridViewTest extends TestCase
{
    public function testAddHasGetRemoveColumn(): void
    {
        $dataGridFactory = $this->createMock(DataGridFactoryInterface::class);

        $dataGrid = $this->createMock(DataGridInterface::class);
        $dataGrid->method('getFactory')->willReturn($dataGridFactory);

        $columnType = $this->createMock(ColumnTypeInterface::class);
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('foo');
        $column->method('getType')->willReturn($columnType);
        $column->method('getDataGrid')->willReturn($dataGrid);

        $dataGridFactory->method('createHeaderView')
            ->willReturnCallback(function () {
                $headerView = $this->createMock(HeaderViewInterface::class);
                $headerView->method('getName')->willReturn('ColumnHeaderView');
                $headerView->method('getType')->willReturn('foo-type');

                return $headerView;
            });

        $columnHeader = $this->createMock(HeaderViewInterface::class);
        $columnHeader->method('getName')->willReturn('foo');
        $columnHeader->method('getType')->willReturn('foo-type');

        $rowset = $this->createMock(DataRowsetInterface::class);
        $gridView = new DataGridView('test-grid-view', [$column], $rowset);

        $this->assertSame('test-grid-view', $gridView->getName());
        $this->assertTrue(isset($gridView->getHeaders()['foo']));
        $this->assertCount(1, $gridView->getHeaders());
    }
}
