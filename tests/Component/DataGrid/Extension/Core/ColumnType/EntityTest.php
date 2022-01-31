<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\ColumnType;

use FSi\Component\DataGrid\ColumnType\Entity;
use FSi\Component\DataGrid\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Component\DataGrid\Fixtures\Entity as Fixture;

final class EntityTest extends TestCase
{
    public function testGetValue(): void
    {
        $columnType = new Entity(
            [new DefaultColumnOptionsExtension()],
            new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor())
        );

        $column = $columnType->createColumn(
            $this->createMock(DataGridInterface::class),
            'foo',
            ['relation_field' => 'author']
        );

        $object = new Fixture('object');
        $object->setAuthor((object) ['foo' => 'bar']);

        $cellView = $columnType->createCellView($column, 1, $object);
        $this->assertSame([['foo' => 'bar']], $cellView->getValue());
    }
}
