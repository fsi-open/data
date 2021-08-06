<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Extension\Core\EventSubscriber;

use FSi\Component\DataGrid\Event\DataGridEventSubscriberInterface;
use FSi\Component\DataGrid\Event\PreBuildViewEvent;

class ColumnOrder implements DataGridEventSubscriberInterface
{
    public static function getPriority(): int
    {
        return 128;
    }

    public function __invoke(PreBuildViewEvent $event): void
    {
        $dataGrid = $event->getDataGrid();
        $columns = $dataGrid->getColumns();

        if (0 === count($columns)) {
            return;
        }
        $positive = [];
        $negative = [];
        $neutral = [];

        $indexedColumns = [];
        foreach ($columns as $column) {
            if ($column->hasOption('display_order')) {
                /** @var int $order */
                $order = $column->getOption('display_order');
                if ($order >= 0) {
                    $positive[$column->getName()] = $order;
                } else {
                    $negative[$column->getName()] = $order;
                }
                $indexedColumns[$column->getName()] = $column;
            } else {
                $neutral[] = $column;
            }
        }

        asort($positive);
        asort($negative);

        $columns = [];
        foreach ($negative as $name => $order) {
            $columns[] = $indexedColumns[$name];
        }

        $columns = array_merge($columns, $neutral);
        foreach ($positive as $name => $order) {
            $columns[] = $indexedColumns[$name];
        }

        $dataGrid->clearColumns();
        foreach ($columns as $column) {
            $dataGrid->addColumnInstance($column);
        }
    }
}
