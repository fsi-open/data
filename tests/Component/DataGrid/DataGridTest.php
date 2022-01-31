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
use FSi\Component\DataGrid\DataGridFactoryInterface;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tests\FSi\Component\DataGrid\Fixtures\ColumnType\FooType;
use Tests\FSi\Component\DataGrid\Fixtures\Entity;

use function array_keys;

final class DataGridTest extends TestCase
{
    private DataGridFactoryInterface $factory;
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

    public function testSetData(): void
    {
        $gridData = [
            new Entity('entity1'),
            new Entity('entity2')
        ];

        $this->datagrid->setData($gridData);

        self::assertSameSize($gridData, $this->datagrid->createView());

        $gridData = [
            ['a' => 'some', 'b' => 'data'],
            ['a' => 'next', 'b' => 'data'],
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
            ['field' => 'one'],
            ['field' => 'two'],
            ['field' => 'three'],
            ['field' => 'four'],
            ['field' => 'bazinga!'],
            ['field' => 'five'],
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
        $dataMapper = $this->createMock(DataMapperInterface::class);
        $dataMapper->method('getData')
            ->willReturnCallback(
                static fn(string $field, $object): ?string => 'name' === $field ? $object->getName() : null
            );

        $this->factory = new DataGridFactory(
            $this->createMock(EventDispatcherInterface::class),
            [new FooType([], $dataMapper)]
        );

        $this->datagrid = new DataGrid(
            $this->factory,
            $this->createMock(EventDispatcherInterface::class),
            'grid'
        );
    }
}
