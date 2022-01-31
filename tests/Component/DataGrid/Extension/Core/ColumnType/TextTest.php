<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\ColumnType;

use FSi\Component\DataGrid\ColumnType\Text;
use FSi\Component\DataGrid\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class TextTest extends TestCase
{
    public function testTrimOption(): void
    {
        $columnType = new Text(
            [new DefaultColumnOptionsExtension()],
            new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor())
        );

        $column = $columnType->createColumn($this->createMock(DataGridInterface::class), 'text', ['trim' => true]);
        $cellView = $columnType->createCellView($column, 1, (object) ['text' => ' VALUE ']);

        $this->assertSame(['text' => 'VALUE'], $cellView->getValue());
    }
}
