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
use FSi\Component\DataGrid\DataGridRowView;
use FSi\Component\DataGrid\Column\CellViewInterface;
use PHPUnit\Framework\TestCase;

class DataGridRowViewTest extends TestCase
{
    public function testCreateDataGridRowView(): void
    {
        $source = ['SOURCE' => 'VALUE'];

        $cellView = $this->createMock(CellViewInterface::class);
        $column = $this->createMock(ColumnInterface::class);
        $columnType = $this->createMock(ColumnTypeInterface::class);

        $column->expects(self::atLeastOnce())->method('getType')->willReturn($columnType);
        $columnType->expects(self::atLeastOnce())
            ->method('createCellView')
            ->with($column, 0, $source)
            ->willReturn($cellView);

        $columns = [
            'foo' => $column
        ];

        $gridRow = new DataGridRowView($columns, 0, $source);
        self::assertSame($gridRow->current(), $cellView);
        self::assertSame($gridRow->getSource(), $source);
    }
}
