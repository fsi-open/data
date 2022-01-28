<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use FSi\Component\DataGrid\ColumnTypeExtension\DefaultColumnOptionsExtension;
use PHPUnit\Framework\TestCase;

class DefaultColumnOptionsExtensionTest extends TestCase
{
    public function testBuildHeaderView(): void
    {
        $extension = new DefaultColumnOptionsExtension();

        $column = $this->createMock(ColumnInterface::class);
        $view = $this->createMock(HeaderViewInterface::class);

        $column->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['label'], ['display_order'])
            ->willReturnOnConsecutiveCalls('foo', 100);

        $view->expects(self::exactly(2))
            ->method('setAttribute')
            ->withConsecutive(['label', 'foo'], ['display_order', 100]);

        $extension->buildHeaderView($column, $view);
    }
}
