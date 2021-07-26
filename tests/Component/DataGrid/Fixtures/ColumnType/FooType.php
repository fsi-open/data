<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Fixtures\ColumnType;

use FSi\Component\DataGrid\Column\ColumnAbstractType;

class FooType extends ColumnAbstractType
{
    public function getId(): string
    {
        return 'foo';
    }

    public function filterValue($value)
    {
        return $value;
    }
}
