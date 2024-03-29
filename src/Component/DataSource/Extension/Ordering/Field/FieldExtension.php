<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Ordering\Field;

use FSi\Component\DataSource\Extension\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Ordering\Storage;
use FSi\Component\DataSource\Extension\Pagination\PaginationExtension;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Field\Type\BooleanTypeInterface;
use FSi\Component\DataSource\Field\Type\DateTimeTypeInterface;
use FSi\Component\DataSource\Field\Type\DateTypeInterface;
use FSi\Component\DataSource\Field\Type\FieldTypeInterface;
use FSi\Component\DataSource\Field\Type\NumberTypeInterface;
use FSi\Component\DataSource\Field\Type\TextTypeInterface;
use FSi\Component\DataSource\Field\Type\TimeTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_combine;
use function array_key_exists;
use function array_keys;
use function array_unshift;
use function array_values;

final class FieldExtension extends FieldAbstractExtension
{
    private Storage $storage;

    public static function getExtendedFieldTypes(): array
    {
        return [
            BooleanTypeInterface::class,
            DateTimeTypeInterface::class,
            DateTypeInterface::class,
            NumberTypeInterface::class,
            TextTypeInterface::class,
            TimeTypeInterface::class,
        ];
    }

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function initOptions(OptionsResolver $optionsResolver, FieldTypeInterface $fieldType): void
    {
        $optionsResolver
            ->setDefined(['default_sort_priority'])
            ->setDefaults([
                'default_sort' => null,
                'sortable' => true
            ])
            ->setAllowedTypes('default_sort_priority', 'integer')
            ->setAllowedTypes('sortable', 'bool')
            ->setAllowedValues('default_sort', [null, 'asc', 'desc'])
        ;
    }

    public function buildView(FieldInterface $field, FieldViewInterface $view): void
    {
        $view->setAttribute('sortable', $field->getOption('sortable'));
        if (false === $field->getOption('sortable')) {
            return;
        }

        $dataSourceName = $field->getDataSourceName();
        $parameters = [$dataSourceName => $this->storage->getDataSourceParameters($dataSourceName)];

        if (0 === $this->storage->getFieldSortingPriority($field)) {
            $view->setAttribute('sorted_ascending', true === $this->storage->isFieldSortingAscending($field));
            $view->setAttribute('sorted_descending', false === $this->storage->isFieldSortingAscending($field));
        } else {
            $view->setAttribute('sorted_ascending', false);
            $view->setAttribute('sorted_descending', false);
        }

        unset($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT][$field->getName()]);

        if (
            false === array_key_exists($dataSourceName, $parameters)
            || false === array_key_exists(OrderingExtension::PARAMETER_SORT, $parameters[$dataSourceName])
        ) {
            $parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT] = [];
        }

        // @FIXME no hacks allowed, resolve this
        // Little hack: we do not know if PaginationExtension is loaded but if
        // it is we don't want page number in sorting URLs.
        unset($parameters[$dataSourceName][PaginationExtension::PARAMETER_PAGE]);

        $fields = array_keys($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT]);
        array_unshift($fields, $field->getName());
        $directions = array_values($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT]);

        $parametersAsc = $parameters;
        $directionsAsc = $directions;
        array_unshift($directionsAsc, 'asc');
        $parametersAsc[$dataSourceName][OrderingExtension::PARAMETER_SORT] = array_combine($fields, $directionsAsc);
        $view->setAttribute('parameters_sort_ascending', $parametersAsc);

        $parametersDesc = $parameters;
        $directionsDesc = $directions;
        array_unshift($directionsDesc, 'desc');
        $parametersDesc[$dataSourceName][OrderingExtension::PARAMETER_SORT] = array_combine($fields, $directionsDesc);
        $view->setAttribute('parameters_sort_descending', $parametersDesc);
    }
}
