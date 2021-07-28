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
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Collection;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Text;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Component\DataGrid\Fixtures\SimpleDataGridExtension;

class TextTest extends TestCase
{
    public function testTrimOption(): void
    {
        $dataGridFactory = new DataGridFactory(
            [new SimpleDataGridExtension(new DefaultColumnOptionsExtension(), new Text())],
            $this->createMock(DataMapperInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );

        $column = $dataGridFactory->createColumn($this->getDataGridMock(), Text::class, 'text', ['trim' => true]);
        $cellView = $dataGridFactory->createCellView($column, 1, (object) ['text' => ' VALUE ']);

        $this->assertSame(['text' => 'VALUE'], $cellView->getValue());
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
