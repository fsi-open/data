<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Ordering\Driver;

use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use FSi\Component\DataSource\Event\DriverEvent;
use FSi\Component\DataSource\Event\DriverEvents;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;

use function array_key_exists;

abstract class DriverExtension extends DriverAbstractExtension
{
    public static function getSubscribedEvents(): array
    {
        return [
            DriverEvents::PRE_GET_RESULT => ['preGetResult'],
        ];
    }

    abstract public function preGetResult(DriverEvent\DriverEventArgs $event): void;

    protected function loadFieldTypesExtensions(): array
    {
        return [
            new FieldExtension(),
        ];
    }

    protected function getFieldExtension(FieldTypeInterface $field): ?FieldExtension
    {
        foreach ($field->getExtensions() as $extension) {
            if (true === $extension instanceof FieldExtension) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * @param array<FieldTypeInterface> $fields
     * @return array<FieldTypeInterface>
     */
    protected function sortFields(array $fields): array
    {
        $sortedFields = [];
        $orderingDirection = [];

        $tmpFields = [];
        foreach ($fields as $field) {
            if ($fieldExtension = $this->getFieldExtension($field)) {
                $fieldOrdering = $fieldExtension->getOrdering($field);
                if (null !== $fieldOrdering) {
                    $tmpFields[$fieldOrdering['priority']] = $field;
                    $orderingDirection[$field->getName()] = $fieldOrdering['direction'];
                }
            }
        }
        ksort($tmpFields);
        foreach ($tmpFields as $field) {
            $fieldName = $field->getName();
            $sortedFields[$fieldName] = $orderingDirection[$fieldName];
        }

        $tmpFields = $fields;
        usort($tmpFields, static function (FieldTypeInterface $a, FieldTypeInterface $b) {
            switch (true) {
                case true === $a->hasOption('default_sort') && false === $b->hasOption('default_sort'):
                    return -1;

                case false === $a->hasOption('default_sort') && true === $b->hasOption('default_sort'):
                    return 1;

                case true === $a->hasOption('default_sort') && true === $b->hasOption('default_sort'):
                    switch (true) {
                        case true === $a->hasOption('default_sort_priority')
                            && false === $b->hasOption('default_sort_priority'):
                            return -1;

                        case false === $a->hasOption('default_sort_priority')
                            && true === $b->hasOption('default_sort_priority'):
                            return 1;

                        case true === $a->hasOption('default_sort_priority')
                            && true === $b->hasOption('default_sort_priority'):
                            return $b->getOption('default_sort_priority') <=> $a->getOption('default_sort_priority');
                    }

                    return 0;

                default:
                    return 0;
            }
        });

        foreach ($tmpFields as $field) {
            if (
                true === $field->hasOption('default_sort')
                && false === array_key_exists($field->getName(), $sortedFields)
            ) {
                $sortedFields[$field->getName()] = $field->getOption('default_sort');
            }
        }

        return $sortedFields;
    }
}
