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
use FSi\Component\DataGrid\Exception\DataGridException;

abstract class DataGridAbstractExtension implements DataGridExtensionInterface
{
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

    /**
     * @return array<ColumnTypeInterface>
     */
    protected function loadColumnTypes(): array
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
}
