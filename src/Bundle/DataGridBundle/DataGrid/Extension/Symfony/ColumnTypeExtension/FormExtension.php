<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension;

use DateTimeInterface;
use FSi\Bundle\DataGridBundle\Form\Type\RowType;
use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use FSi\Component\DataGrid\Extension\Core\ColumnType\DateTime;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Number;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Text;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Entity;
use FSi\Component\DataGrid\Extension\Gedmo\ColumnType\Tree;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;

class FormExtension extends ColumnAbstractTypeExtension
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

    public function bindData(ColumnInterface $column, $data, $object, $index): void
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

        $form = $this->createForm($column, $index, $object);
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

    public function buildCellView(ColumnInterface $column, CellViewInterface $view): void
    {
        if (false === $column->getOption('editable')) {
            return;
        }

        $source = $view->getAttribute('source');
        $index = $view->getAttribute('index');
        $form = $this->createForm($column, $index, $source);

        $view->setAttribute('form', $form->createView());
    }

    public function getExtendedColumnTypes(): array
    {
        return [
            Text::class,
            Boolean::class,
            Number::class,
            DateTime::class,
            Entity::class,
            Tree::class,
        ];
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'editable' => false,
            'form_options' => [],
            'form_type' => [],
        ]);

        $optionsResolver->setAllowedTypes('editable', 'bool');
        $optionsResolver->setAllowedTypes('form_options', 'array');
        $optionsResolver->setAllowedTypes('form_type', 'array');
    }

    /**
     * @param ColumnInterface $column
     * @param int|string $index
     * @param array<string,mixed>|object $object
     * @return FormInterface<FormInterface>
     */
    private function createForm(ColumnInterface $column, $index, $object): FormInterface
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
