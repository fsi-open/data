<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Core;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\ColumnType\Action;
use FSi\Component\DataGrid\ColumnType\Batch;
use FSi\Component\DataGrid\ColumnType\DateTime;
use FSi\Component\DataGrid\ColumnType\Entity;
use FSi\Component\DataGrid\ColumnType\Money;
use FSi\Component\DataGrid\ColumnType\Number;
use FSi\Component\DataGrid\ColumnType\Text;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\Event\PreBuildViewEvent;
use FSi\Component\DataGrid\EventSubscriber\ColumnOrder;
use FSi\Component\DataGrid\Extension\CoreExtension;
use PHPUnit\Framework\TestCase;

use function array_map;
use function count;

class CoreExtensionTest extends TestCase
{
    public function testLoadedTypes(): void
    {
        $extension = new CoreExtension();
        $this->assertTrue($extension->hasColumnType('text'));
        $this->assertTrue($extension->hasColumnType(Text::class));
        $this->assertTrue($extension->hasColumnType('number'));
        $this->assertTrue($extension->hasColumnType(Number::class));
        $this->assertTrue($extension->hasColumnType('datetime'));
        $this->assertTrue($extension->hasColumnType(DateTime::class));
        $this->assertTrue($extension->hasColumnType('action'));
        $this->assertTrue($extension->hasColumnType(Action::class));
        $this->assertTrue($extension->hasColumnType('money'));
        $this->assertTrue($extension->hasColumnType(Money::class));
        $this->assertTrue($extension->hasColumnType('entity'));
        $this->assertTrue($extension->hasColumnType(Entity::class));
        $this->assertTrue($extension->hasColumnType('batch'));
        $this->assertTrue($extension->hasColumnType(Batch::class));

        $this->assertFalse($extension->hasColumnType('foo'));
    }

    public function testColumnOrder(): void
    {
        $subscriber = new ColumnOrder();

        $cases = [
            [
                'columns' => [
                    'negative2' => -2,
                    'neutral1' => null,
                    'negative1' => -1,
                    'neutral2' => null,
                    'positive1' => 1,
                    'neutral3' => null,
                    'positive2' => 2,
                ],
                'sorted' => [
                    'negative2',
                    'negative1',
                    'neutral1',
                    'neutral2',
                    'neutral3',
                    'positive1',
                    'positive2',
                ]
            ],
            [
                'columns' => [
                    'neutral1' => null,
                    'neutral2' => null,
                    'neutral3' => null,
                    'neutral4' => null,
                ],
                'sorted' => [
                    'neutral1',
                    'neutral2',
                    'neutral3',
                    'neutral4',
                ]
            ]
        ];

        foreach ($cases as $case) {
            $columns = [];

            foreach ($case['columns'] as $name => $order) {
                $column = $this->createMock(ColumnInterface::class);

                $column->method('getName')->willReturn($name);

                $column
                    ->expects($this->atLeastOnce())
                    ->method('hasOption')
                    ->willReturnCallback(static function ($attribute) use ($order) {
                        return ('display_order' === $attribute) && (null !== $order);
                    });

                $column->method('getOption')
                    ->willReturnCallback(static function ($attribute) use ($order) {
                        if ('display_order' === $attribute) {
                            return $order;
                        }

                        return null;
                    });

                $columns[$name] = $column;
            }

            $dataGrid = $this->createMock(DataGridInterface::class);

            $dataGrid->expects(self::once())
                ->method('getColumns')
                ->willReturn($columns);

            $dataGrid->expects(self::once())->method('clearColumns');

            $sortedColumns = array_map(
                function (string $columnName) use ($columns): array {
                    return [$columns[$columnName]];
                },
                $case['sorted']
            );
            $dataGrid
                ->expects($this->exactly(count($case['sorted'])))
                ->method('addColumnInstance')
                ->withConsecutive(...$sortedColumns)
                ->will($this->returnSelf());

            $event = new PreBuildViewEvent($dataGrid);

            $subscriber($event);
        }
    }
}
