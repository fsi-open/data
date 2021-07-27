<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Data\DataRowsetInterface;
use FSi\Component\DataGrid\Data\DataRowset;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\Event\PostBindDataEvent;
use FSi\Component\DataGrid\Event\PostBuildViewEvent;
use FSi\Component\DataGrid\Event\PostSetDataEvent;
use FSi\Component\DataGrid\Event\PreBindDataEvent;
use FSi\Component\DataGrid\Event\PreBuildViewEvent;
use FSi\Component\DataGrid\Event\PreSetDataEvent;
use FSi\Component\DataGrid\Exception\DataGridException;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;

use function sprintf;

final class DataGrid implements DataGridInterface
{
    private DataGridFactoryInterface $dataGridFactory;
    private string $name;
    private DataMapperInterface $dataMapper;
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @var array<string,ColumnInterface>
     */
    private array $columns = [];
    private ?DataRowsetInterface $rowset = null;

    public function __construct(
        string $name,
        DataGridFactoryInterface $dataGridFactory,
        DataMapperInterface $dataMapper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->name = $name;
        $this->dataGridFactory = $dataGridFactory;
        $this->dataMapper = $dataMapper;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getFactory(): DataGridFactoryInterface
    {
        return $this->dataGridFactory;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addColumn(string $name, string $type = 'text', array $options = []): DataGridInterface
    {
        return $this->addColumnInstance(
            $this->dataGridFactory->createColumn($this, $type, $name, $options)
        );
    }

    public function addColumnInstance(ColumnInterface $column): DataGridInterface
    {
        if ($column->getDataGrid() !== $this) {
            throw new InvalidArgumentException('Tried to add column associated with different datagrid instance');
        }

        $this->columns[$column->getName()] = $column;

        return $this;
    }

    public function removeColumn(string $name): DataGridInterface
    {
        if (false === $this->hasColumn($name)) {
            throw new InvalidArgumentException(sprintf('Column "%s" does not exist in data grid.', $name));
        }

        unset($this->columns[$name]);

        return $this;
    }

    public function clearColumns(): DataGridInterface
    {
        $this->columns = [];

        return $this;
    }

    public function getColumn(string $name): ColumnInterface
    {
        if (false === $this->hasColumn($name)) {
            throw new InvalidArgumentException(sprintf(
                'Column "%s" does not exist in data grid.',
                $name
            ));
        }

        return $this->columns[$name];
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function hasColumn(string $name): bool
    {
        return array_key_exists($name, $this->columns);
    }

    public function hasColumnType(string $type): bool
    {
        foreach ($this->columns as $column) {
            if ($column->getType()->getId() === $type) {
                return true;
            }
        }

        return false;
    }

    public function createView(): DataGridViewInterface
    {
        $event = new PreBuildViewEvent($this);
        $this->eventDispatcher->dispatch($event);

        $view = new DataGridView($this->name, $this->columns, $this->getRowset());

        $this->eventDispatcher->dispatch(new PostBuildViewEvent($this, $view));

        return (new PostBuildViewEvent($this, $view))->getDataGridView();
    }

    public function setData(iterable $data): void
    {
        $event = new PreSetDataEvent($this, $data);
        $this->eventDispatcher->dispatch($event);
        $data = $event->getData();
        if (false === is_iterable($data)) {
            throw new InvalidArgumentException(sprintf(
                'The data returned by the "DataGridEvents::PRE_SET_DATA" class needs to be iterable, "%s" given!',
                is_object($data) ? get_class($data) : gettype($data)
            ));
        }

        $this->rowset = new DataRowset($data);

        $this->eventDispatcher->dispatch(new PostSetDataEvent($this, $this->rowset));
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
                $columnType = $column->getType();

                foreach ($this->dataGridFactory->getColumnTypeExtensions($columnType) as $extension) {
                    $extension->bindData($column, $index, $source, $values);
                }
            }
        }

        $this->eventDispatcher->dispatch(new PostBindDataEvent($this, $data));
    }

    public function getDataMapper(): DataMapperInterface
    {
        return $this->dataMapper;
    }

    private function getRowset(): DataRowsetInterface
    {
        if (null === $this->rowset) {
            throw new DataGridException(
                'Before you will be able to crete view from DataGrid you need to call method setData'
            );
        }

        return $this->rowset;
    }
}
