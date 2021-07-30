<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Column\CellView;
use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\Column;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\Column\HeaderView;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use FSi\Component\DataGrid\Exception\DataGridException;
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;
use function count;
use function sprintf;

final class DataGridFactory implements DataGridFactoryInterface
{
    /**
     * @var array<DataGridExtensionInterface>
     */
    private array $extensions = [];
    private DataMapperInterface $dataMapper;
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @var array<DataGridInterface>
     */
    private array $dataGrids = [];
    /**
     * @var array<ColumnTypeInterface>
     */
    private array $columnTypes = [];
    private ?EditableDataGridFormHandlerInterface $formHandler;

    /**
     * @param iterable<DataGridExtensionInterface> $extensions
     * @param DataMapperInterface $dataMapper
     * @param EventDispatcherInterface $eventDispatcher
     * @param EditableDataGridFormHandlerInterface|null $formHandler
     */
    public function __construct(
        iterable $extensions,
        DataMapperInterface $dataMapper,
        EventDispatcherInterface $eventDispatcher,
        ?EditableDataGridFormHandlerInterface $formHandler = null
    ) {
        foreach ($extensions as $extension) {
            if (false === $extension instanceof DataGridExtensionInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Each extension must implement "%s"',
                    DataGridExtensionInterface::class
                ));
            }

            $this->extensions[] = $extension;
        }

        $this->dataMapper = $dataMapper;
        $this->eventDispatcher = $eventDispatcher;
        $this->formHandler = $formHandler;
    }

    public function hasColumnType(string $type): bool
    {
        if (true === array_key_exists($type, $this->columnTypes)) {
            return true;
        }

        try {
            $this->loadColumnType($type);
        } catch (UnexpectedTypeException $e) {
            return false;
        }

        return true;
    }

    public function getColumnType(string $type): ColumnTypeInterface
    {
        if (true === $this->hasColumnType($type)) {
            return $this->columnTypes[$type];
        }

        $this->loadColumnType($type);

        return $this->columnTypes[$type];
    }

    /**
     * @param ColumnTypeInterface $columnType
     * @return array<ColumnTypeExtensionInterface>
     */
    public function getColumnTypeExtensions(ColumnTypeInterface $columnType): array
    {
        $extensions = [];
        foreach ($this->extensions as $extension) {
            if ($extension->hasColumnTypeExtensions($columnType)) {
                $extensions[] = $extension->getColumnTypeExtensions($columnType);
            }
        }

        if (0 === count($extensions)) {
            return [];
        }

        return array_merge(...$extensions);
    }

    public function getDataMapper(): DataMapperInterface
    {
        return $this->dataMapper;
    }

    public function createDataGrid(string $name): DataGridInterface
    {
        if (true === array_key_exists($name, $this->dataGrids)) {
            throw new DataGridColumnException(sprintf(
                'Datagrid name "%s" is not uniqe, it was used before to create datagrid',
                $name
            ));
        }

        $this->dataGrids[$name] = new DataGrid($name, $this, $this->dataMapper, $this->eventDispatcher);

        return $this->dataGrids[$name];
    }

    public function createEditableDataGrid(string $name): EditableDataGridInterface
    {
        if (null === $this->formHandler) {
            throw new DataGridException(sprintf(
                '%s implementation is required to create editable DataGrids',
                EditableDataGridFormHandlerInterface::class
            ));
        }

        if (true === array_key_exists($name, $this->dataGrids)) {
            throw new DataGridColumnException(sprintf(
                'Datagrid name "%s" is not uniqe, it was used before to create datagrid',
                $name
            ));
        }

        $this->dataGrids[$name] = new EditableDataGrid(
            $name,
            $this,
            $this->dataMapper,
            $this->eventDispatcher,
            $this->formHandler
        );

        return $this->dataGrids[$name];
    }

    public function createColumn(
        DataGridInterface $dataGrid,
        string $type,
        string $name,
        array $options
    ): ColumnInterface {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');

        $columnType = $this->getColumnType($type);
        $columnType->initOptions($optionsResolver);
        foreach ($this->getColumnTypeExtensions($columnType) as $extension) {
            $extension->initOptions($optionsResolver);
        }

        return new Column(
            $dataGrid,
            $columnType,
            $name,
            $optionsResolver->resolve(array_merge(['name' => $name], $options))
        );
    }

    public function createCellView(ColumnInterface $column, $index, $source): CellViewInterface
    {
        $columnType = $column->getType();
        $value = $columnType->filterValue($column, $columnType->getValue($column, $source));
        foreach ($this->getColumnTypeExtensions($columnType) as $extension) {
            $value = $extension->filterValue($column, $value);
        }

        $cellView = new CellView($column, $value);
        $columnType->buildCellView($column, $cellView);
        foreach ($this->getColumnTypeExtensions($columnType) as $extension) {
            $extension->buildCellView($column, $cellView);
        }

        return $cellView;
    }

    public function createHeaderView(ColumnInterface $column): HeaderViewInterface
    {
        $view = new HeaderView($column);

        $columnType = $column->getType();
        $columnType->buildHeaderView($column, $view);
        foreach ($this->getColumnTypeExtensions($columnType) as $extension) {
            $extension->buildHeaderView($column, $view);
        }

        return $view;
    }

    /**
     * @param string|class-string<ColumnTypeInterface> $type
     * @throws UnexpectedTypeException
     */
    private function loadColumnType(string $type): void
    {
        if (true === array_key_exists($type, $this->columnTypes)) {
            return;
        }

        $typeInstance = null;
        foreach ($this->extensions as $extension) {
            if (true === $extension->hasColumnType($type)) {
                $typeInstance = $extension->getColumnType($type);
                break;
            }
        }

        if (null === $typeInstance) {
            throw new UnexpectedTypeException(sprintf(
                'There is no column with type "%s" registered in factory.',
                $type
            ));
        }

        $this->columnTypes[$type] = $typeInstance;
    }
}
