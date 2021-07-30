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

class DataGrid implements DataGridInterface
{
    protected DataGridFactoryInterface $dataGridFactory;
    protected EventDispatcherInterface $eventDispatcher;
    protected ?DataRowsetInterface $rowset = null;
    private string $name;
    private DataMapperInterface $dataMapper;
    /**
     * @var array<string,ColumnInterface>
     */
    private array $columns = [];

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
            throw new InvalidArgumentException(sprintf(
                'Tried to add column "%s" associated with datagrid "%s" to datagrid "%s"',
                $column->getName(),
                $column->getDataGrid()->getName(),
                $this->name
            ));
        }

        $this->columns[$column->getName()] = $column;

        return $this;
    }

    public function removeColumn(string $name): DataGridInterface
    {
        if (false === $this->hasColumn($name)) {
            throw new InvalidArgumentException("Column \"{$name}\" does not exist in datagrid \"{$this->name}\".");
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
        $this->rowset = new DataRowset($event->getData());
        $this->eventDispatcher->dispatch(new PostSetDataEvent($this, $this->rowset));
    }

    public function getDataMapper(): DataMapperInterface
    {
        return $this->dataMapper;
    }

    protected function getRowset(): DataRowsetInterface
    {
        if (null === $this->rowset) {
            throw new DataGridException(
                'Before you will be able to crete view from DataGrid you need to call method setData'
            );
        }

        return $this->rowset;
    }
}
