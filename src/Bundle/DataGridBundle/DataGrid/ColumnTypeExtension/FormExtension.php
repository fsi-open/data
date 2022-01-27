<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\ColumnTypeExtension;

use FSi\Bundle\DataGridBundle\DataGrid\CellFormBuilder\CellFormBuilderInterface;
use FSi\Bundle\DataGridBundle\Form\Type\RowType;
use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\DataGridCellFormHandlerInterface;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;
use function array_keys;
use function get_class;
use function implode;

final class FormExtension extends ColumnAbstractTypeExtension implements DataGridCellFormHandlerInterface
{
    /**
     * @var array<class-string<ColumnTypeInterface>,CellFormBuilderInterface>
     */
    private array $cellFormBuilders = [];
    private FormFactoryInterface $formFactory;
    private bool $csrfProtectionEnabled;
    /**
     * @var array<string,FormInterface<FormInterface>>
     */
    private array $forms = [];

    public static function getExtendedColumnTypes(): array
    {
        return [ColumnAbstractType::class];
    }

    /**
     * @param iterable<CellFormBuilderInterface> $cellFormBuilders
     * @param FormFactoryInterface $formFactory
     * @param bool $csrfProtectionEnabled
     */
    public function __construct(
        iterable $cellFormBuilders,
        FormFactoryInterface $formFactory,
        bool $csrfProtectionEnabled = true
    ) {
        foreach ($cellFormBuilders as $cellFormBuilder) {
            foreach ($cellFormBuilder::getSupportedColumnTypes() as $supportedColumnType) {
                $this->cellFormBuilders[$supportedColumnType] = $cellFormBuilder;
            }
        }
        $this->formFactory = $formFactory;
        $this->csrfProtectionEnabled = $csrfProtectionEnabled;
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

    public function submit(ColumnInterface $column, $index, $source, $data): void
    {
        if (false === $column->getOption('editable')) {
            return;
        }

        $formData = $this->getFormBuilder($column)->prepareFormData($column, $data);
        $form = $this->getForm($column, $index, $source);
        $form->submit([$index => $formData]);
        if (true === $form->isSubmitted() && true === $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $fields) {
                foreach ($fields as $field => $value) {
                    $column->getDataGrid()->getDataMapper()->setData($field, $source, $value);
                }
            }
        }
    }

    public function buildCellView(ColumnInterface $column, CellViewInterface $view, $index, $source): void
    {
        if (false === $column->getOption('editable')) {
            return;
        }

        $view->setAttribute('form', $this->getForm($column, $index, $source)->createView());
    }

    public function isValid(ColumnInterface $column, $index): bool
    {
        $form = $this->getForm($column, $index, null);

        return true === $form->isSubmitted() && true === $form->isValid();
    }

    /**
     * @param ColumnInterface $column
     * @param int|string $index
     * @param array<string,mixed>|object|null $object
     * @return FormInterface<FormInterface>
     */
    private function getForm(ColumnInterface $column, $index, $object): FormInterface
    {
        $formId = implode([$column->getDataGrid()->getName(), $column->getName(), $index]);
        if (true === array_key_exists($formId, $this->forms)) {
            return $this->forms[$formId];
        }

        if (null === $object) {
            throw new DataGridColumnException(
                "DataGrid '{$column->getDataGrid()->getName()}' does not have data bound"
            );
        }

        $formBuilderOptions = ['entry_type' => RowType::class];
        if (true === $this->csrfProtectionEnabled) {
            $formBuilderOptions['csrf_protection'] = false;
        }
        $fields = $this->getFormBuilder($column)->prepareFormFields(
            $column,
            $object,
            $column->getOption('form_type'),
            $column->getOption('form_options')
        );

        $formBuilderOptions['entry_options']['fields'] = $fields;

        $formData = [];
        foreach (array_keys($fields) as $fieldName) {
            $formData[$fieldName] = $column->getDataGrid()->getDataMapper()->getData($fieldName, $object);
        }

        $formBuilder = $this->formFactory->createNamedBuilder(
            $column->getDataGrid()->getName(),
            CollectionType::class,
            [$index => $formData],
            $formBuilderOptions
        );

        $this->forms[$formId] = $formBuilder->getForm();
        return $this->forms[$formId];
    }

    private function getFormBuilder(ColumnInterface $column): CellFormBuilderInterface
    {
        $columnTypeClass = get_class($column->getType());
        foreach ($this->cellFormBuilders as $supportedColumnType => $cellFormBuilder) {
            if (true === is_a($columnTypeClass, $supportedColumnType, true)) {
                return $cellFormBuilder;
            }
        }

        throw new DataGridColumnException("Unable to find CellFormBuilder for column of class \"{$columnTypeClass}\"");
    }
}
