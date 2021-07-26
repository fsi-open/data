<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Fixtures;

use FSi\Component\DataGrid\DataGridAbstractExtension;
use Tests\FSi\Component\DataGrid\Fixtures\ColumnType;

class FooExtension extends DataGridAbstractExtension
{
    protected function loadColumnTypes(): array
    {
        return [
            new ColumnType\FooType(),
        ];
    }
}
