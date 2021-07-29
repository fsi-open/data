<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony;

use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\DataGridExtensionInterface;
use FSi\Component\DataGrid\Exception\DataGridException;
use InvalidArgumentException;

use function array_filter;
use function array_merge;

use const ARRAY_FILTER_USE_KEY;

class DependencyInjectionExtension implements DataGridExtensionInterface
{
    /**
     * @var array<string,ColumnTypeInterface>
     */
    private array $columnTypes = [];
    /**
     * @var array<string,array<ColumnTypeExtensionInterface>>
     */
    private array $columnTypesExtensions = [];

    /**
     * @param iterable<ColumnTypeInterface> $columnTypes
     * @param iterable<ColumnTypeExtensionInterface> $columnTypesExtensions
     */
    public function __construct(iterable $columnTypes, iterable $columnTypesExtensions)
    {
        foreach ($columnTypes as $columnType) {
            $this->columnTypes[$columnType->getId()] = $columnType;
            $this->columnTypes[get_class($columnType)] = $columnType;
        }

        foreach ($columnTypesExtensions as $columnTypeExtension) {
            foreach ($columnTypeExtension->getExtendedColumnTypes() as $extendedColumnType) {
                if (false === array_key_exists($extendedColumnType, $this->columnTypesExtensions)) {
                    $this->columnTypesExtensions[$extendedColumnType] = [];
                }
                $this->columnTypesExtensions[$extendedColumnType][] = $columnTypeExtension;
            }
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

    public function hasColumnTypeExtensions(ColumnTypeInterface $type): bool
    {
        foreach (array_keys($this->columnTypesExtensions) as $extendedType) {
            if (true === is_a($type, $extendedType)) {
                return true;
            }
        }

        return false;
    }

    public function getColumnTypeExtensions(ColumnTypeInterface $type): array
    {
        $result = array_filter(
            $this->columnTypesExtensions,
            static fn(string $extendedType): bool => is_a($type, $extendedType),
            ARRAY_FILTER_USE_KEY
        );

        if (0 === count($result)) {
            throw new DataGridException(sprintf(
                'Extension for column type "%s" can not be loaded by this DataGrid extension',
                get_class($type)
            ));
        }

        return array_merge(...array_values($result));
    }
}
