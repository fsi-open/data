<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Extension\Core;

use FSi\Component\DataGrid\DataGridAbstractExtension;
use FSi\Component\DataGrid\Extension\Core\ColumnType;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension;

class CoreExtension extends DataGridAbstractExtension
{
    protected function loadColumnTypes(): array
    {
        return [
            new ColumnType\Text([
                new ColumnTypeExtension\DefaultColumnOptionsExtension(),
                new ColumnTypeExtension\ValueFormatColumnOptionsExtension(),
            ]),
            new ColumnType\Number([
                new ColumnTypeExtension\DefaultColumnOptionsExtension(),
                new ColumnTypeExtension\ValueFormatColumnOptionsExtension(),
            ]),
            new ColumnType\Collection([
                new ColumnTypeExtension\DefaultColumnOptionsExtension(),
                new ColumnTypeExtension\ValueFormatColumnOptionsExtension(),
            ]),
            new ColumnType\DateTime([
                new ColumnTypeExtension\DefaultColumnOptionsExtension(),
                new ColumnTypeExtension\ValueFormatColumnOptionsExtension(),
            ]),
            new ColumnType\Money([
                new ColumnTypeExtension\DefaultColumnOptionsExtension(),
                new ColumnTypeExtension\ValueFormatColumnOptionsExtension(),
            ]),
            new ColumnType\Entity([
                new ColumnTypeExtension\DefaultColumnOptionsExtension(),
                new ColumnTypeExtension\EntityValueFormatColumnOptionsExtension(),
            ]),
            new ColumnType\Action([
                new ColumnTypeExtension\DefaultColumnOptionsExtension(),
            ]),
            new ColumnType\Batch(),
        ];
    }
}
