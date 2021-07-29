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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DependencyInjectionExtension implements DataGridExtensionInterface
{
    /**
     * @var array<ColumnTypeInterface>
     */
    private array $columnTypes = [];

    /**
     * @var array<string,array<ColumnTypeExtensionInterface>>
     */
    private array $columnTypesExtensions = [];

    /**
     * @param ColumnTypeInterface[] $columnTypes
     * @param ColumnTypeExtensionInterface[] $columnTypesExtensions
     */
    public function __construct(array $columnTypes, array $columnTypesExtensions)
    {
        foreach ($columnTypes as $columnType) {
            $this->columnTypes[$columnType->getId()] = $columnType;
        }

        foreach ($columnTypesExtensions as $columnTypeExtension) {
            foreach ($columnTypeExtension->getExtendedColumnTypes() as $extendedColumnType) {
                if (!array_key_exists($extendedColumnType, $this->columnTypesExtensions)) {
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
        if (!array_key_exists($type, $this->columnTypes)) {
            throw new \InvalidArgumentException(sprintf(
                'The column type "%s" is not registered with the service container.',
                $type
            ));
        }

        return $this->columnTypes[$type];
    }

    public function hasColumnTypeExtensions(ColumnTypeInterface $type): bool
    {
        return array_key_exists($type->getId(), $this->columnTypesExtensions);
    }

    /**
     * @param ColumnTypeInterface $type
     * @return array<ColumnTypeExtensionInterface>
     */
    public function getColumnTypeExtensions(ColumnTypeInterface $type): array
    {
        if (false === array_key_exists($type->getId(), $this->columnTypesExtensions)) {
            return [];
        }

        return $this->columnTypesExtensions[$type->getId()];
    }
}
