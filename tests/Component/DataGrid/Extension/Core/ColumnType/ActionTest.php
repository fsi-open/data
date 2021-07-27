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
use FSi\Component\DataGrid\Extension\Core\ColumnType\Action;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Component\DataGrid\Fixtures\SimpleDataGridExtension;

class ActionTest extends TestCase
{
    private DataGridFactoryInterface $dataGridFactory;

    public function testEmptyActionsOptionType(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            Action::class,
            'action',
            ['actions' => 'boo']
        );
    }

    public function testInvalidActionInActionsOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Action::class, 'action', [
            'actions' => [
                'edit' => 'asasdas'
            ],
            'field_mapping' => ['foo']
        ]);
        $this->dataGridFactory->createCellView($column, (object) ['foo' => 'bar']);
    }

    public function testRequiredActionInActionsOption()
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Action::class, 'action', [
            'actions' => [
                'edit' => [
                    'uri_scheme' => '/test/%s',
                ]
            ],
            'field_mapping' => ['foo'],
        ]);
        $cellView = $this->dataGridFactory->createCellView($column, (object) ['foo' => 'bar']);

        $this->assertSame([
            'edit' => [
                'url' => '/test/bar',
                'field_mapping_values' => [
                    'foo' => 'bar'
                ]
            ]
        ], $cellView->getValue());
    }

    public function testAvailableActionInActionsOption()
    {
        $column = $this->dataGridFactory->createColumn($this->getDataGridMock(), Action::class, 'action', [
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
        $cellView = $this->dataGridFactory->createCellView($column, (object) ['foo' => 'bar']);

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
        $this->dataGridFactory = new DataGridFactory(
            [new SimpleDataGridExtension(new DefaultColumnOptionsExtension(), new Action())],
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
