<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Core\ColumnType;

use FSi\Component\DataGrid\DataGridFactoryInterface;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Action;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ActionTest extends TestCase
{
    private Action $columnType;

    public function testEmptyActionsOptionType(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->columnType->createColumn($this->getDataGridMock(), 'action', ['actions' => 'boo']);
    }

    public function testInvalidActionInActionsOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'action', [
            'actions' => [
                'edit' => 'asasdas'
            ],
            'field_mapping' => ['foo']
        ]);
        $this->columnType->createCellView($column, 1, (object) ['foo' => 'bar']);
    }

    public function testRequiredActionInActionsOption(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'action', [
            'actions' => [
                'edit' => [
                    'uri_scheme' => '/test/%s',
                ]
            ],
            'field_mapping' => ['foo'],
        ]);
        $cellView = $this->columnType->createCellView($column, 1, (object) ['foo' => 'bar']);

        $this->assertSame([
            'edit' => [
                'url' => '/test/bar',
                'field_mapping_values' => [
                    'foo' => 'bar'
                ]
            ]
        ], $cellView->getValue());
    }

    public function testAvailableActionInActionsOption(): void
    {
        $column = $this->columnType->createColumn($this->getDataGridMock(), 'action', [
            'actions' => [
                'edit' => [
                    'uri_scheme' => '/test/%s',
                    'domain' => 'fsi.pl',
                    'protocol' => 'https://',
                    'redirect_uri' => 'http://onet.pl/'
                ]
            ],
            'field_mapping' => ['foo']
        ]);
        $cellView = $this->columnType->createCellView($column, 1, (object) ['foo' => 'bar']);

        $this->assertSame([
            'edit' => [
                'url' => 'https://fsi.pl/test/bar?redirect_uri=' . urlencode('http://onet.pl/'),
                'field_mapping_values' => [
                    'foo' => 'bar'
                ]
            ]
        ], $cellView->getValue());
    }

    protected function setUp(): void
    {
        $this->columnType = new Action([new DefaultColumnOptionsExtension()]);
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
