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
use FSi\Component\DataSource\Field\FieldExtensionInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function array_key_exists;

abstract class DriverAbstractExtension implements DriverExtensionInterface, EventSubscriberInterface
{
    /**
     * @var null|array<FieldTypeInterface>
     */
    private $fieldTypes;

    /**
     * @var null|array<string, array<FieldExtensionInterface>>
     */
    private $fieldTypesExtensions;

    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function hasFieldType(string $type): bool
    {
        if (null === $this->fieldTypes) {
            $this->initFieldsTypes();
        }

        return array_key_exists($type, $this->fieldTypes);
    }

    public function getFieldType(string $type): FieldTypeInterface
    {
        if (null === $this->fieldTypes) {
            $this->initFieldsTypes();
        }

        if (false === array_key_exists($type, $this->fieldTypes)) {
            throw new DataSourceException(sprintf('Field with type "%s" can\'t be loaded.', $type));
        }

        return $this->fieldTypes[$type];
    }

    public function hasFieldTypeExtensions(string $type): bool
    {
        if (null === $this->fieldTypesExtensions) {
            $this->initFieldTypesExtensions();
        }

        return array_key_exists($type, $this->fieldTypesExtensions);
    }

    public function getFieldTypeExtensions(string $type): array
    {
        if (null === $this->fieldTypesExtensions) {
            $this->initFieldTypesExtensions();
        }

        if (false === array_key_exists($type, $this->fieldTypesExtensions)) {
            throw new DataSourceException(sprintf('Field extensions with type "%s" can\'t be loaded.', $type));
        }

        return $this->fieldTypesExtensions[$type];
    }

    public function loadSubscribers(): array
    {
        return [$this];
    }

    protected function loadFieldTypesExtensions(): array
    {
        return [];
    }

    protected function loadFieldTypes(): array
    {
        return [];
    }

    /**
     * @throws DataSourceException
     */
    private function initFieldsTypes(): void
    {
        $this->fieldTypes = [];

        $fieldTypes = $this->loadFieldTypes();

        foreach ($fieldTypes as $fieldType) {
            if (false === $fieldType instanceof FieldTypeInterface) {
                throw new DataSourceException(
                    sprintf('Expected instance of %s, "%s" given.', FieldTypeInterface::class, get_class($fieldType))
                );
            }

            if (true === array_key_exists($fieldType->getType(), $this->fieldTypes)) {
                throw new DataSourceException(
                    sprintf('Error during field types loading. Name "%s" already in use.', $fieldType->getType())
                );
            }

            $this->fieldTypes[$fieldType->getType()] = $fieldType;
        }
    }

    /**
     * @throws DataSourceException
     */
    private function initFieldTypesExtensions(): void
    {
        $this->fieldTypesExtensions = [];
        $fieldTypesExtensions = $this->loadFieldTypesExtensions();

        foreach ($fieldTypesExtensions as $extension) {
            if (false === $extension instanceof FieldExtensionInterface) {
                throw new DataSourceException(
                    sprintf("Expected instance of %s but %s got", FieldExtensionInterface::class, get_class($extension))
                );
            }

            $types = $extension->getExtendedFieldTypes();
            foreach ($types as $type) {
                if (false === array_key_exists($type, $this->fieldTypesExtensions)) {
                    $this->fieldTypesExtensions[$type] = [];
                }
                $this->fieldTypesExtensions[$type][] = $extension;
            }
        }
    }
}
