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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tests\FSi\Component\DataGrid\Fixtures\FooExtension;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use Tests\FSi\Component\DataGrid\Fixtures\ColumnType\FooType;
use PHPUnit\Framework\TestCase;

class DataGridFactoryTest extends TestCase
{
    /**
     * @var DataGridFactoryInterface
     */
    private $factory;

    protected function setUp(): void
    {
        $extensions = [
            new FooExtension(),
        ];

        $dataMapper = $this->createMock(DataMapperInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->factory = new DataGridFactory($extensions, $dataMapper, $eventDispatcher);
    }

    public function testCreateGrids(): void
    {
        $grid = $this->factory->createDataGrid('grid');
        self::assertSame('grid', $grid->getName());

        $this->expectException(DataGridColumnException::class);
        $this->expectExceptionMessage('Datagrid name "grid" is not uniqe, it was used before to create datagrid');
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
        $this->expectExceptionMessage('There is no column with type "bar" registered in factory.');
        $this->factory->getColumnType('bar');
    }

    public function testGetDataMapper(): void
    {
        self::assertInstanceOf(DataMapperInterface::class, $this->factory->getDataMapper());
    }
}
