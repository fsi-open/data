<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid;

use FSi\Component\DataGrid\DataGridFactory;
use FSi\Component\DataGrid\DataGridFactoryInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\Exception\DataGridException;
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tests\FSi\Component\DataGrid\Fixtures\ColumnType\FooType;

final class DataGridFactoryTest extends TestCase
{
    private DataGridFactoryInterface $factory;

    public function testCreateGrids(): void
    {
        $grid = $this->factory->createDataGrid('grid');
        self::assertSame('grid', $grid->getName());

        $this->expectException(DataGridException::class);
        $this->expectExceptionMessage('Datagrid name "grid" is not unique.');
        $this->factory->createDataGrid('grid');
    }

    public function testHasColumnType(): void
    {
        self::assertTrue($this->factory->hasColumnType('foo'));
        self::assertFalse($this->factory->hasColumnType('bar'));
    }

    public function testGetColumnType(): void
    {
        self::assertInstanceOf(FooType::class, $this->factory->getColumnType('foo'));

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Unsupported column type "bar".');
        $this->factory->getColumnType('bar');
    }

    protected function setUp(): void
    {
        $this->factory = new DataGridFactory(
            $this->createMock(EventDispatcherInterface::class),
            [new FooType([], $this->createMock(DataMapperInterface::class))]
        );
    }
}
