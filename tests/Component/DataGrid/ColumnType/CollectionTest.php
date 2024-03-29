<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\ColumnType;

use FSi\Component\DataGrid\ColumnType\Collection;
use FSi\Component\DataGrid\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class CollectionTest extends TestCase
{
    public function testFilterValue(): void
    {
        $columnType = new Collection(
            [new DefaultColumnOptionsExtension()],
            new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor())
        );

        $column = $columnType->createColumn($this->createMock(DataGridInterface::class), 'col', [
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
}
