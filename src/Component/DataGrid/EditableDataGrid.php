<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\Event\PostBindDataEvent;
use FSi\Component\DataGrid\Event\PostBuildViewEvent;
use FSi\Component\DataGrid\Event\PreBindDataEvent;
use FSi\Component\DataGrid\Event\PreBuildViewEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EditableDataGrid extends DataGrid implements EditableDataGridInterface
{
    private EditableDataGridFormHandlerInterface $formHandler;

    public function __construct(
        string $name,
        DataGridFactoryInterface $dataGridFactory,
        DataMapperInterface $dataMapper,
        EventDispatcherInterface $eventDispatcher,
        EditableDataGridFormHandlerInterface $formHandler
    ) {
        parent::__construct($name, $dataGridFactory, $dataMapper, $eventDispatcher);

        $this->formHandler = $formHandler;
    }

    public function bindData($data): void
    {
        $event = new PreBindDataEvent($this, $data);
        $this->eventDispatcher->dispatch($event);
        $data = $event->getData();

        foreach ($data as $index => $values) {
            if (false === isset($this->rowset[$index])) {
                continue;
            }

            $source = $this->rowset[$index];

            foreach ($this->getColumns() as $column) {
                $this->formHandler->bindData($column, $index, $source, $values);
            }
        }

        $this->eventDispatcher->dispatch(new PostBindDataEvent($this, $data));
    }

    public function createView(): DataGridViewInterface
    {
        $event = new PreBuildViewEvent($this);
        $this->eventDispatcher->dispatch($event);

        $view = new EditableDataGridView($this->formHandler, $this->getName(), $this->getColumns(), $this->getRowset());

        $this->eventDispatcher->dispatch(new PostBuildViewEvent($this, $view));

        return (new PostBuildViewEvent($this, $view))->getDataGridView();
    }
}
