<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony;

use DateTimeInterface;
use FSi\Bundle\DataGridBundle\Form\Type\RowType;
use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\EditableDataGridFormHandlerInterface;
use FSi\Component\DataGrid\EditableDataGridInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use function array_key_exists;
use function array_keys;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;

final class EditableDataGridFormHandler implements EditableDataGridFormHandlerInterface
{
    private FormFactoryInterface $formFactory;
    private bool $csrfProtectionEnabled;
    /**
     * @var array<string,FormInterface>
     */
    private array $forms = [];

    public function __construct(FormFactoryInterface $formFactory, bool $csrfProtectionEnabled = true)
    {
        $this->formFactory = $formFactory;
        $this->csrfProtectionEnabled = $csrfProtectionEnabled;
    }

    public function bindData(ColumnInterface $column, $index, $object, $data): void
    {
        if (false === $column->getOption('editable')) {
            return;
        }

        $formData = [];
        switch ($column->getType()->getId()) {
            case 'entity':
                $relationField = $column->getOption('relation_field');
                if (false === array_key_exists($relationField, $data)) {
                    return;
                }

                $formData[$relationField] = $data[$relationField];
                break;

            default:
                $fieldMapping = $column->getOption('field_mapping');
                foreach ($fieldMapping as $field) {
                    if (false === array_key_exists($field, $data)) {
                        return;
                    }

                    $formData[$field] = $data[$field];
                }
        }

        $form = $this->getForm($column, $index, $object);
        $form->submit([$index => $formData]);
        if (true === $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $fields) {
                foreach ($fields as $field => $value) {
                    $column->getDataGrid()->getDataMapper()->setData($field, $object, $value);
                }
            }
        }
    }

    public function buildCellView(ColumnInterface $column, CellViewInterface $view, $index, $object): void
    {
        if (false === $column->getOption('editable')) {
            return;
        }

        $view->setAttribute('form', $this->getForm($column, $index, $object)->createView());
    }

    /**
     * @param ColumnInterface $column
     * @param int|string $index
     * @param array<string,mixed>|object $object
     * @return FormInterface<FormInterface>
     */
    private function getForm(ColumnInterface $column, $index, $object): FormInterface
    {
        $formId = implode([$column->getName(), $column->getType()->getId(), $index]);
        if (true === array_key_exists($formId, $this->forms)) {
            return $this->forms[$formId];
        }

        // Create fields array. There are column types like entity where field_mapping
        // should not be used to build field array.
        $fields = [];
        switch ($column->getType()->getId()) {
            case 'entity':
                /** @var string $relationField */
                $relationField = $column->getOption('relation_field');
                $field = [
                    'name' => $relationField,
                    'type' => EntityType::class,
                    'options' => [],
                ];

                $fields[$relationField] = $field;
                break;

            default:
                /** @var array<string> $fieldMapping */
                $fieldMapping = $column->getOption('field_mapping');
                foreach ($fieldMapping as $fieldName) {
                    $field = [
                        'name' => $fieldName,
                        'type' => null,
                        'options' => [],
                    ];
                    $fields[$fieldName] = $field;
                }
        }

        //Pass fields form options from column into $fields array.
        /** @var array<string,array<string,mixed>> $fieldsOptions */
        $fieldsOptions = $column->getOption('form_options');
        foreach ($fieldsOptions as $fieldName => $fieldOptions) {
            if (true === array_key_exists($fieldName, $fields)) {
                if (true === is_array($fieldOptions)) {
                    $fields[$fieldName]['options'] = $fieldOptions;
                }
            }
        }

        //Pass fields form type from column into $fields array.
        /** @var array<string,string> $fieldsTypes */
        $fieldsTypes = $column->getOption('form_type');
        foreach ($fieldsTypes as $fieldName => $fieldType) {
            if (true === array_key_exists($fieldName, $fields)) {
                if (true === is_string($fieldType)) {
                    $fields[$fieldName]['type'] = $fieldType;
                }
            }
        }

        //Build data array, the data array holds data that should be passed into
        //form elements.
        switch ($column->getType()->getId()) {
            case 'datetime':
                foreach ($fields as &$field) {
                    $value = $column->getDataGrid()->getDataMapper()->getData($field['name'], $object);
                    if (null === $field['type']) {
                        $field['type'] = DateTimeType::class;
                    }
                    if (true === is_numeric($value) && false === array_key_exists('input', $field['options'])) {
                        $field['options']['input'] = 'timestamp';
                    }
                    if (true === is_string($value) && false === array_key_exists('input', $field['options'])) {
                        $field['options']['input'] = 'string';
                    }
                    if (
                        true === $value instanceof DateTimeInterface
                        && false === array_key_exists('input', $field['options'])
                    ) {
                        $field['options']['input'] = 'datetime';
                    }
                }
                break;
        }

        $formBuilderOptions = ['entry_type' => RowType::class];
        if (true === $this->csrfProtectionEnabled) {
            $formBuilderOptions['csrf_protection'] = false;
        }
        $formBuilderOptions['entry_options']['fields'] = $fields;

        $formData = [];
        foreach (array_keys($fields) as $fieldName) {
            $formData[$fieldName] = $column->getDataGrid()->getDataMapper()->getData($fieldName, $object);
        }

        //Create form builder.
        $formBuilder = $this->formFactory->createNamedBuilder(
            $column->getDataGrid()->getName(),
            CollectionType::class,
            [$index => $formData],
            $formBuilderOptions
        );

        //Create Form.
        $this->forms[$formId] = $formBuilder->getForm();

        return $this->forms[$formId];
    }
}
