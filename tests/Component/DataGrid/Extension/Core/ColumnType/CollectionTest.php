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
use FSi\Component\DataGrid\Extension\Core\ColumnType\Collection;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CollectionTest extends TestCase
{
    public function testFilterValue(): void
    {
        $columnType = new Collection([new DefaultColumnOptionsExtension()]);

        $column = $columnType->createColumn($this->getDataGridMock(), 'col', [
            'collection_glue' => ', ',
            'field_mapping' => ['collection1', 'collection2'],
        ]);

        $cellView = $columnType->createCellView($column, 1, (object) [
            'collection1' => ['foo', 'bar'],
            'collection2' => 'test',
        ]);

        $this->assertSame(
            [
                'collection1' => 'foo, bar',
                'collection2' => 'test'
            ],
            $cellView->getValue()
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
