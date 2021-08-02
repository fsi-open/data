<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Core\ColumnType;

use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Component\DataGrid\Fixtures\Entity as Fixture;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Entity;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\DataGridInterface;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    public function testGetValue(): void
    {
        $columnType = new Entity([new DefaultColumnOptionsExtension()]);

        $dataGrid = $this->getDataGridMock();
        $column = $columnType->createColumn($dataGrid, 'foo', ['relation_field' => 'author']);

        $object = new Fixture('object');
        $object->setAuthor((object) ['foo' => 'bar']);

        $cellView = $columnType->createCellView($column, 1, $object);
        $this->assertSame([['foo' => 'bar']], $cellView->getValue());
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
