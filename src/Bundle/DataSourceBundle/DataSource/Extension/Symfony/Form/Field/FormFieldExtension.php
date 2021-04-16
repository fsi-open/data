<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Type\BetweenType;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_key_exists;
use function array_merge;

class FormFieldExtension extends FieldAbstractExtension
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array<FormInterface>
     */
    protected $forms = [];

    /**
     * Original values of input parameters for each supported field.
     *
     * @var array<mixed>
     */
    protected $parameters = [];

    public function __construct(FormFactoryInterface $formFactory, TranslatorInterface $translator)
    {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FieldEvents::PRE_BIND_PARAMETER => ['preBindParameter'],
            FieldEvents::POST_BUILD_VIEW => ['postBuildView'],
            FieldEvents::POST_GET_PARAMETER => ['preGetParameter'],
        ];
    }

    public function getExtendedFieldTypes(): array
    {
        return ['text', 'number', 'date', 'time', 'datetime', 'entity', 'boolean'];
    }

    public function initOptions(FieldTypeInterface $field): void
    {
        $field->getOptionsResolver()
            ->setDefaults([
                'form_filter' => true,
                'form_options' => [],
                'form_from_options' => [],
                'form_to_options' => [],
                'form_type' => null,
            ])
            ->setDefined([
                'form_order'
            ])
            ->setAllowedTypes('form_filter', 'bool')
            ->setAllowedTypes('form_options', 'array')
            ->setAllowedTypes('form_from_options', 'array')
            ->setAllowedTypes('form_to_options', 'array')
            ->setAllowedTypes('form_order', 'integer')
            ->setAllowedTypes('form_type', ['string', 'null'])
            ->setNormalizer('form_type', static function (Options $options, ?string $type) use ($field): ?string {
                if (null !== $type) {
                    return $type;
                }

                if ('isNull' === $field->getComparison()) {
                    return ChoiceType::class;
                }

                switch ($field->getType()) {
                    case 'boolean':
                        return ChoiceType::class;
                    case 'text':
                        return TextType::class;
                    case 'number':
                        return NumberType::class;
                    case 'date':
                        return DateType::class;
                    case 'time':
                        return TimeType::class;
                    case 'datetime':
                        return DateTimeType::class;
                    case 'entity':
                        return EntityType::class;
                    default:
                        return null;
                }
            })
            ->setNormalizer('form_options', function (Options $options, array $formOptions) use ($field): array {
                if ('isNull' === $field->getComparison() && ChoiceType::class === $options['form_type']) {
                    return array_merge([
                        'placeholder' => '',
                        'choices' => [
                            $this->translator->trans('datasource.form.choices.is_null', [], 'DataSourceBundle')
                                => 'null',
                            $this->translator->trans('datasource.form.choices.is_not_null', [], 'DataSourceBundle')
                                => 'no_null',
                        ],
                    ], $formOptions);
                }

                if ('boolean' === $field->getType() && ChoiceType::class === $options['form_type']) {
                    return array_merge([
                        'placeholder' => '',
                        'choices' => [
                            $this->translator->trans('datasource.form.choices.yes', [], 'DataSourceBundle') => '1',
                            $this->translator->trans('datasource.form.choices.no', [], 'DataSourceBundle') => '0'
                        ],
                    ], $formOptions);
                }

                return $formOptions;
            })
            ->setNormalizer('form_from_options', static function (Options $options, array $formFromOptions): array {
                return array_merge($options['form_options'], $formFromOptions);
            })
            ->setNormalizer('form_to_options', static function (Options $options, array $formToOptions): array {
                return array_merge($options['form_options'], $formToOptions);
            })
        ;
    }

    public function postBuildView(FieldEvent\ViewEventArgs $event): void
    {
        $field = $event->getField();
        $view = $event->getView();

        $form = $this->getForm($field);
        if (null !== $form) {
            $view->setAttribute('form', $form->createView());
        }
    }

    public function preBindParameter(FieldEvent\ParameterEventArgs $event): void
    {
        $field = $event->getField();
        $form = $this->getForm($field);
        if (null === $form) {
            return;
        }

        $fieldOid = spl_object_hash($field);
        $parameter = $event->getParameter();

        if ($form->isSubmitted()) {
            $form = $this->getForm($field, true);
        }

        $datasourceName = null !== $field->getDataSource() ? $field->getDataSource()->getName() : null;
        if (null === $datasourceName) {
            return;
        }

        if (true === $this->hasParameterValue($parameter, $field)) {
            $this->parameters[$fieldOid] = $this->getParameterValue($parameter, $field);

            $fieldForm = $form->get(DataSourceInterface::PARAMETER_FIELDS)->get($field->getName());
            $fieldForm->submit($this->parameters[$fieldOid]);
            $data = $fieldForm->getData();

            if (null !== $data) {
                $this->setParameterValue($parameter, $field, $data);
            } else {
                $this->clearParameterValue($parameter, $field);
            }

            $event->setParameter($parameter);
        }
    }

    public function preGetParameter(FieldEvent\ParameterEventArgs $event): void
    {
        $field = $event->getField();
        $fieldOid = spl_object_hash($field);

        if (true === array_key_exists($fieldOid, $this->parameters)) {
            $parameters = [];
            $this->setParameterValue($parameters, $field, $this->parameters[$fieldOid]);
            $event->setParameter($parameters);
        }
    }

    protected function getForm(FieldTypeInterface $field, bool $force = false): ?FormInterface
    {
        $datasource = $field->getDataSource();
        if (null === $datasource) {
            return null;
        }

        if (false === $field->getOption('form_filter')) {
            return null;
        }

        $fieldOid = spl_object_hash($field);
        if (true === array_key_exists($fieldOid, $this->parameters) && false === $force) {
            return $this->forms[$fieldOid];
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

        switch ($field->getComparison()) {
            case 'between':
                $this->buildBetweenComparisonForm($fieldsForm, $field, $options);
                break;

            default:
                $fieldsForm->add($field->getName(), $field->getOption('form_type'), $options);
        }

        $form->add($fieldsForm);
        $this->forms[$fieldOid] = $form;

        return $this->forms[$fieldOid];
    }

    protected function buildBetweenComparisonForm(FormInterface $form, FieldTypeInterface $field, array $options): void
    {
        $betweenBuilder = $this->getFormFactory()->createNamedBuilder(
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

    protected function getFormFactory(): FormFactoryInterface
    {
        return $this->formFactory;
    }

    private function hasParameterValue(array $array, FieldTypeInterface $field): bool
    {
        return isset(
            $array[$field->getDataSource()->getName()][DataSourceInterface::PARAMETER_FIELDS][$field->getName()]
        );
    }

    /**
     * @param array $array
     * @param FieldTypeInterface $field
     * @return mixed
     */
    private function getParameterValue(array $array, FieldTypeInterface $field)
    {
        return $array[$field->getDataSource()->getName()][DataSourceInterface::PARAMETER_FIELDS][$field->getName()];
    }

    /**
     * @param array $array
     * @param FieldTypeInterface $field
     * @param mixed $value
     */
    private function setParameterValue(array &$array, FieldTypeInterface $field, $value): void
    {
        $array[$field->getDataSource()->getName()][DataSourceInterface::PARAMETER_FIELDS][$field->getName()] = $value;
    }

    private function clearParameterValue(array &$array, FieldTypeInterface $field): void
    {
        unset($array[$field->getDataSource()->getName()][DataSourceInterface::PARAMETER_FIELDS][$field->getName()]);
    }
}
