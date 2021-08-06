<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony;

use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\DataGridExtensionInterface;
use InvalidArgumentException;

class DependencyInjectionExtension implements DataGridExtensionInterface
{
    /**
     * @var array<string,ColumnTypeInterface>
     */
    private array $columnTypes = [];

    /**
     * @param iterable<ColumnTypeInterface> $columnTypes
     */
    public function __construct(iterable $columnTypes)
    {
        foreach ($columnTypes as $columnType) {
            $this->columnTypes[$columnType->getId()] = $columnType;
            $this->columnTypes[get_class($columnType)] = $columnType;
        }
    }

    public function hasColumnType(string $type): bool
    {
        return array_key_exists($type, $this->columnTypes);
    }

    public function getColumnType(string $type): ColumnTypeInterface
    {
        if (false === array_key_exists($type, $this->columnTypes)) {
            throw new InvalidArgumentException(
                "The column type \"{$type}\" is not registered with the service container."
            );
        }

        return $this->columnTypes[$type];
    }
}
