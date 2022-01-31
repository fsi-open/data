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
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\Exception\DataGridException;
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use Psr\EventDispatcher\EventDispatcherInterface;

use function array_key_exists;

final class DataGridFactory implements DataGridFactoryInterface
{
    private DataMapperInterface $dataMapper;
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @var array<string, DataGridInterface>
     */
    private array $dataGrids;
    /**
     * @var array<ColumnTypeInterface>
     */
    private array $columnTypes;

    /**
     * @param DataMapperInterface $dataMapper
     * @param EventDispatcherInterface $eventDispatcher
     * @param iterable<ColumnTypeInterface> $columnTypes
     */
    public function __construct(
        DataMapperInterface $dataMapper,
        EventDispatcherInterface $eventDispatcher,
        iterable $columnTypes
    ) {
        $this->dataMapper = $dataMapper;
        $this->eventDispatcher = $eventDispatcher;
        $this->dataGrids = [];
        $this->columnTypes = [];
        foreach ($columnTypes as $columnType) {
            $this->columnTypes[$columnType->getId()] = $columnType;
        }
    }

    public function hasColumnType(string $type): bool
    {
        return array_key_exists($type, $this->columnTypes);
    }

    public function getColumnType(string $type): ColumnTypeInterface
    {
        if (false === $this->hasColumnType($type)) {
            throw new UnexpectedTypeException("Unsupported column type \"{$type}\".");
        }

        return $this->columnTypes[$type];
    }

    public function createDataGrid(string $name): DataGridInterface
    {
        if (true === array_key_exists($name, $this->dataGrids)) {
            throw new DataGridException("Datagrid name \"{$name}\" is not unique.");
        }

        $this->dataGrids[$name] = new DataGrid(
            $this,
            $this->dataMapper,
            $this->eventDispatcher,
            $name
        );

        return $this->dataGrids[$name];
    }
}
