<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Type\BetweenType;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use function array_key_exists;
use function array_merge;

final class FormStorage
{
    private FormFactoryInterface $formFactory;

    /**
     * @var array<string,FormInterface<FormInterface>>
     */
    private array $forms = [];

    /**
     * @var array<string,mixed>
     */
    private array $parameters = [];

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param FieldInterface $field
     * @param mixed $parameter
     */
    public function setParameter(FieldInterface $field, $parameter): void
    {
        $this->parameters[$this->getFieldKey($field)] = $parameter;
    }

    /**
     * @param FieldInterface $field
     * @return mixed
     */
    public function getParameter(FieldInterface $field)
    {
        return $this->parameters[$this->getFieldKey($field)] ?? null;
    }

    /**
     * @param FieldInterface $field
     * @param bool $force
     * @return FormInterface<FormInterface>|null
     */
    public function getForm(FieldInterface $field, bool $force = false): ?FormInterface
    {
        $datasource = $field->getDataSource();

        if (false === $field->getOption('form_filter')) {
            return null;
        }

        $fieldKey = $this->getFieldKey($field);
        if (true === array_key_exists($fieldKey, $this->forms) && false === $force) {
            return $this->forms[$fieldKey];
        }

        $options = array_merge(
            $field->getOption('form_options'),
            ['required' => false, 'auto_initialize' => false]
        );

        $form = $this->formFactory->createNamed(
            $datasource->getName(),
            CollectionType::class,
            null,
            ['csrf_protection' => false]
        );
        $fieldsForm = $this->formFactory->createNamed(
            DataSourceInterface::PARAMETER_FIELDS,
            FormType::class,
            null,
            ['auto_initialize' => false]
        );

        switch ($field->getOption('comparison')) {
            case 'between':
                $this->buildBetweenComparisonForm($fieldsForm, $field, $options);
                break;

            default:
                $fieldsForm->add($field->getName(), $field->getOption('form_type'), $options);
        }

        $form->add($fieldsForm);
        $this->forms[$fieldKey] = $form;

        return $this->forms[$fieldKey];
    }

    /**
     * @param FormInterface<FormInterface> $form
     * @param FieldInterface $field
     * @param array<string,mixed> $options
     */
    protected function buildBetweenComparisonForm(FormInterface $form, FieldInterface $field, array $options): void
    {
        $betweenBuilder = $this->formFactory->createNamedBuilder(
            $field->getName(),
            BetweenType::class,
            null,
            $options
        );

        $fromOptions = $field->getOption('form_from_options');
        $toOptions = $field->getOption('form_to_options');
        $fromOptions = array_merge($options, $fromOptions);
        $toOptions = array_merge($options, $toOptions);
        $type = $field->getOption('form_type');

        $betweenBuilder->add('from', $type, $fromOptions);
        $betweenBuilder->add('to', $type, $toOptions);

        $form->add($betweenBuilder->getForm());
    }

    private function getFieldKey(FieldInterface $field): string
    {
        return "{$field->getDataSource()->getName()}-{$field->getName()}";
    }
}
