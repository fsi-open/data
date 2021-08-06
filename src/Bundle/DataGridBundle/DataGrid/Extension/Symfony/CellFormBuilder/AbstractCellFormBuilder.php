<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\CellFormBuilder;

use FSi\Component\DataGrid\Column\ColumnInterface;
use Symfony\Component\Form\FormTypeInterface;

use function array_key_exists;

abstract class AbstractCellFormBuilder implements CellFormBuilderInterface
{
    public function prepareFormData(ColumnInterface $column, $data): array
    {
        $formData = [];

        /** @var array<string> $fieldMapping */
        $fieldMapping = $column->getOption('field_mapping');
        foreach ($fieldMapping as $field) {
            if (false === array_key_exists($field, $data)) {
                continue;
            }

            $formData[$field] = $data[$field];
        }

        return $formData;
    }

    public function prepareFormFields(ColumnInterface $column, $object, array $formTypes, array $options): array
    {
        $fields = [];
        /** @var array<string> $fieldMapping */
        $fieldMapping = $column->getOption('field_mapping');
        foreach ($fieldMapping as $fieldName) {
            $field = [
                'name' => $fieldName,
                'type' => $formTypes[$fieldName] ?? $this->getDefaultFormType(),
                'options' => $options[$fieldName] ?? [],
            ];
            $fields[$fieldName] = $field;
        }

        return $fields;
    }

    /**
     * @return class-string<FormTypeInterface>
     */
    abstract protected function getDefaultFormType(): string;
}
