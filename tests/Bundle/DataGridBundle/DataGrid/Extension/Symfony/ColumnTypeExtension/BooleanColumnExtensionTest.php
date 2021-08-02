<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension;

use FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension\FormExtension;
use FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension\BooleanColumnExtension;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanColumnExtensionTest extends TestCase
{
    public function testColumnOptions(): void
    {
        $columnType = new Boolean([new FormExtension(), new BooleanColumnExtension($this->getTranslator())]);
        $column = $columnType->createColumn($this->createMock(DataGridInterface::class), 'grid', []);

        $this->assertEquals('YES', $column->getOption('true_value'));
        $this->assertEquals('NO', $column->getOption('false_value'));
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function getTranslator(): TranslatorInterface
    {
        /** @var TranslatorInterface&MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->expects(self::atLeast(2))
            ->method('trans')
            ->withConsecutive(
                ['datagrid.boolean.yes', [], 'DataGridBundle'],
                ['datagrid.boolean.no', [], 'DataGridBundle']
            )->willReturnOnConsecutiveCalls('YES', 'NO');

        return $translator;
    }
}
