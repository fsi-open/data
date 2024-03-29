<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver;

use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\Type\FieldTypeInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

use function array_key_exists;
use function array_walk;
use function get_class;

/**
 * @template T
 * @template-implements DriverInterface<T>
 */
abstract class AbstractDriver implements DriverInterface
{
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @var array<FieldTypeInterface>
     */
    private array $fieldTypes;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param array<FieldTypeInterface> $fieldTypes
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, array $fieldTypes)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->fieldTypes = [];
        array_walk($fieldTypes, function (FieldTypeInterface $fieldType): void {
            $this->fieldTypes[$fieldType->getId()] = $fieldType;
            $this->fieldTypes[get_class($fieldType)] = $fieldType;
        });
    }

    public function hasFieldType(string $type): bool
    {
        return array_key_exists($type, $this->fieldTypes);
    }

    public function getFieldType(string $type): FieldTypeInterface
    {
        if (false === $this->hasFieldType($type)) {
            throw new DataSourceException("Unsupported field type (\"{$type}\").");
        }

        return $this->fieldTypes[$type];
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
