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
use FSi\Component\DataGrid\Exception\UnexpectedTypeException;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use InvalidArgumentException;

use function array_key_exists;

class DataGridFactory implements DataGridFactoryInterface
{
    /**
     * @var array<DataGridInterface>
     */
    private array $dataGrids = [];
    /**
     * @var array<ColumnTypeInterface>
     */
    private array $columnTypes = [];
    /**
     * @var array<DataGridExtensionInterface>
     */
    private array $extensions;
    private DataMapperInterface $dataMapper;

    /**
     * @param DataGridExtensionInterface[] $extensions
     * @param DataMapperInterface $dataMapper
     * @throws InvalidArgumentException
     */
    public function __construct(array $extensions, DataMapperInterface $dataMapper)
    {
        foreach ($extensions as $extension) {
            if (false === $extension instanceof DataGridExtensionInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Each extension must implement "%s"',
                    DataGridExtensionInterface::class
                ));
            }
        }

        $this->dataMapper = $dataMapper;
        $this->extensions = $extensions;
    }

    public function createDataGrid(string $name = 'grid'): DataGridInterface
    {
        if (true === array_key_exists($name, $this->dataGrids)) {
            throw new DataGridColumnException(sprintf(
                'Datagrid name "%s" is not uniqe, it was used before to create datagrid',
                $name
            ));
        }

        $this->dataGrids[$name] = new DataGrid($name, $this, $this->dataMapper);

        return $this->dataGrids[$name];
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
            return clone $this->columnTypes[$type];
        }

        $this->loadColumnType($type);

        return clone $this->columnTypes[$type];
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getDataMapper(): DataMapperInterface
    {
        return $this->dataMapper;
    }

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

        foreach ($this->extensions as $extension) {
            if (true === $extension->hasColumnTypeExtensions($type)) {
                $columnExtensions = $extension->getColumnTypeExtensions($type);
                foreach ($columnExtensions as $columnExtension) {
                    $typeInstance->addExtension($columnExtension);
                }
            }
        }

        $this->columnTypes[$type] = $typeInstance;
    }
}
