<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid;

use FSi\Component\DataGrid\DataGrid;
use FSi\Component\DataGrid\DataGridFactory;
use FSi\Component\DataGrid\DataGridInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tests\FSi\Component\DataGrid\Fixtures\FooExtension;
use Tests\FSi\Component\DataGrid\Fixtures\ColumnType\FooType;
use Tests\FSi\Component\DataGrid\Fixtures\Entity;
use FSi\Component\DataGrid\DataGridViewInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\DataGridFactoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TypeError;

class DataGridTest extends TestCase
{
    private DataGridFactoryInterface $factory;
    private DataMapperInterface $dataMapper;
    private DataGridInterface $datagrid;

    public function testGetName(): void
    {
        self::assertSame('grid', $this->datagrid->getName());
    }

    public function testHasAddGetRemoveClearColumn(): void
    {
        self::assertFalse($this->datagrid->hasColumn('foo1'));
        $this->datagrid->addColumn('foo1', 'foo');
        self::assertTrue($this->datagrid->hasColumn('foo1'));
        self::assertTrue($this->datagrid->hasColumnType('foo'));
        self::assertFalse($this->datagrid->hasColumnType('this_type_cant_exists'));

        self::assertInstanceOf(FooType::class, $this->datagrid->getColumn('foo1')->getType());

        self::assertTrue($this->datagrid->hasColumn('foo1'));
        $column = $this->datagrid->getColumn('foo1');

        $this->datagrid->removeColumn('foo1');
        self::assertFalse($this->datagrid->hasColumn('foo1'));

        $this->datagrid->addColumnInstance($column);
        self::assertEquals($column, $this->datagrid->getColumn('foo1'));

        self::assertCount(1, $this->datagrid->getColumns());

        $this->datagrid->clearColumns();
        self::assertCount(0, $this->datagrid->getColumns());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "bar" does not exist in data grid.');
        $this->datagrid->getColumn('bar');
    }

    public function testGetDataMapper(): void
    {
        self::assertInstanceOf(DataMapperInterface::class, $this->datagrid->getDataMapper());
    }

    public function testSetData(): void
    {
        $gridData = [
            new Entity('entity1'),
            new Entity('entity2')
        ];

        $this->datagrid->setData($gridData);

        self::assertSameSize($gridData, $this->datagrid->createView());

        $gridData = [
            ['some', 'data'],
            ['next', 'data']
        ];

        $this->datagrid->setData($gridData);

        self::assertSameSize($gridData, $this->datagrid->createView());
    }

    public function testCreateView(): void
    {
        $this->datagrid->addColumn('foo1', 'foo');
        $gridData = [
            new Entity('entity1'),
            new Entity('entity2')
        ];

        $this->datagrid->setData($gridData);
        $view = $this->datagrid->createView();
        self::assertCount(2, $view);
    }

    public function testSetDataForArray(): void
    {
        $gridData = [
            ['one'],
            ['two'],
            ['three'],
            ['four'],
            ['bazinga!'],
            ['five'],
        ];

        $this->datagrid->setData($gridData);
        $view = $this->datagrid->createView();

        $keys = [];
        foreach ($view as $row) {
            $keys[] = $row->getIndex();
        }

        self::assertEquals(array_keys($gridData), $keys);
    }

    protected function setUp(): void
    {
        $this->dataMapper = $this->createMock(DataMapperInterface::class);
        $this->dataMapper->method('getData')
            ->willReturnCallback(
                function ($field, $object) {
                    if ('name' === $field) {
                        return $object->getName();
                    }

                    return null;
                }
            );

        $this->dataMapper->method('setData')
            ->willReturnCallback(
                function ($field, $object, $value) {
                    if ('name' === $field) {
                        $object->setName($value);
                    }
                }
            );

        $this->factory = new DataGridFactory(
            [new FooExtension()],
            $this->dataMapper,
            $this->createMock(EventDispatcherInterface::class)
        );

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->datagrid = new DataGrid('grid', $this->factory, $this->dataMapper, $eventDispatcher);
    }
}
