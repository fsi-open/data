<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use FSi\Component\DataGrid\Exception\DataGridException;
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;

use function array_key_exists;
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
