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
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\Exception\DataGridException;

abstract class DataGridAbstractExtension implements DataGridExtensionInterface
{
    /**
     * @var array<string|class-string<ColumnTypeInterface>,array<ColumnTypeExtensionInterface>>
     */
    protected array $columnTypesExtensions = [];

    /**
     * @var array<string|class-string<ColumnTypeInterface>,ColumnTypeInterface>
     */
    protected array $columnTypes = [];

    public function getColumnType(string $type): ColumnTypeInterface
    {
        if (0 === count($this->columnTypes)) {
            $this->initColumnTypes();
        }

        if (false === array_key_exists($type, $this->columnTypes)) {
            throw new DataGridException(sprintf(
                'The column type "%s" can not be loaded by DataGrid extension "%s"',
                $type,
                static::class
            ));
        }

        return $this->columnTypes[$type];
    }

    public function hasColumnType(string $type): bool
    {
        if (0 === count($this->columnTypes)) {
            $this->initColumnTypes();
        }

        return array_key_exists($type, $this->columnTypes);
    }

    public function hasColumnTypeExtensions(ColumnTypeInterface $type): bool
    {
        if (0 === count($this->columnTypes)) {
            $this->initColumnTypesExtensions();
        }

        foreach (array_keys($this->columnTypesExtensions) as $extendedType) {
            if (true === is_a($type, $extendedType)) {
                return true;
            }
        }

        return false;
    }

    public function getColumnTypeExtensions(ColumnTypeInterface $type): array
    {
        if (0 === count($this->columnTypes)) {
            $this->initColumnTypesExtensions();
        }

        foreach ($this->columnTypesExtensions as $extendedType => $extensions) {
            if (true === is_a($type, $extendedType)) {
                return $extensions;
            }
        }

        throw new DataGridException(sprintf(
            'Extension for column type "%s" can not be loaded by DataGrid extension "%s"',
            get_class($type),
            static::class
        ));
    }

    /**
     * @return array<ColumnTypeInterface>
     */
    protected function loadColumnTypes(): array
    {
        return [];
    }

    /**
     * @return array<ColumnTypeExtensionInterface>
     */
    protected function loadColumnTypesExtensions(): array
    {
        return [];
    }

    private function initColumnTypes(): void
    {
        $this->columnTypes = [];

        $columnTypes = $this->loadColumnTypes();

        foreach ($columnTypes as $columnType) {
            $this->columnTypes[$columnType->getId()] = $columnType;
            $this->columnTypes[get_class($columnType)] = $columnType;
        }
    }

    private function initColumnTypesExtensions(): void
    {
        $columnTypesExtensions = $this->loadColumnTypesExtensions();

        foreach ($columnTypesExtensions as $extension) {
            $types = $extension->getExtendedColumnTypes();
            foreach ($types as $type) {
                if (false === array_key_exists($type, $this->columnTypesExtensions)) {
                    $this->columnTypesExtensions[$type] = [];
                }

                $this->columnTypesExtensions[$type][] = $extension;
            }
        }
    }
}
