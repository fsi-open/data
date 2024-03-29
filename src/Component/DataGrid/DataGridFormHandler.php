<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Event\PostSubmitEvent;
use FSi\Component\DataGrid\Event\PreSubmitEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

final class DataGridFormHandler implements DataGridFormHandlerInterface
{
    private EventDispatcherInterface $eventDispatcher;
    private DataGridCellFormHandlerInterface $dataGridCellFormHandler;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DataGridCellFormHandlerInterface $dataGridCellFormHandler
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->dataGridCellFormHandler = $dataGridCellFormHandler;
    }

    /**
     * @param DataGridInterface $dataGrid
     * @param mixed $data
     */
    public function submit(DataGridInterface $dataGrid, $data): void
    {
        $event = new PreSubmitEvent($dataGrid, $data);
        $this->eventDispatcher->dispatch($event);
        $data = $event->getData();

        foreach ($dataGrid as $index => $source) {
            $values = $data[$index] ?? [];

            foreach ($dataGrid->getColumns() as $column) {
                $this->dataGridCellFormHandler->submit($column, $index, $source, $values);
            }
        }

        $this->eventDispatcher->dispatch(new PostSubmitEvent($dataGrid, $data));
    }

    public function isValid(DataGridInterface $dataGrid): bool
    {
        foreach ($dataGrid as $index => $source) {
            foreach ($dataGrid->getColumns() as $column) {
                $isValid = $this->dataGridCellFormHandler->isValid($column, $index);
                if (false === $isValid) {
                    return false;
                }
            }
        }

        return true;
    }
}
