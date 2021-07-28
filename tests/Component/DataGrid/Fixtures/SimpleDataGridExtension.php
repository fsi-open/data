<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Fixtures;

use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\DataGridExtensionInterface;
use RuntimeException;

class SimpleDataGridExtension implements DataGridExtensionInterface
{
    private ColumnTypeExtensionInterface $columnTypeExtension;
    private ?ColumnTypeInterface $columnType;

    public function __construct(ColumnTypeExtensionInterface $columnTypeExtension, ?ColumnTypeInterface $columnType)
    {
        $this->columnTypeExtension = $columnTypeExtension;
        $this->columnType = $columnType;
    }

    public function hasColumnType(string $type): bool
    {
        return null !== $this->columnType
            && ($this->columnType->getId() === $type || true === is_a($this->columnType, $type));
    }

    public function getColumnType(string $type): ColumnTypeInterface
    {
        if (null === $this->columnType) {
            throw new RuntimeException(sprintf('Column of type "%s" does not exist', $type));
        }

        return $this->columnType;
    }

    public function hasColumnTypeExtensions(ColumnTypeInterface $type): bool
    {
        foreach ($this->columnTypeExtension->getExtendedColumnTypes() as $extendedColumnType) {
            if (true === is_a($type, $extendedColumnType)) {
                return true;
            }
        }

        return false;
    }

    public function getColumnTypeExtensions(ColumnTypeInterface $type): array
    {
        foreach ($this->columnTypeExtension->getExtendedColumnTypes() as $extendedColumnType) {
            if (true === is_a($type, $extendedColumnType)) {
                return [$this->columnTypeExtension];
            }
        }

        return [];
    }
}
