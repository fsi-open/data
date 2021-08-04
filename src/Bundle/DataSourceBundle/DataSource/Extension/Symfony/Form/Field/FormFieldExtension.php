<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\FormStorage;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Field\Type\BooleanTypeInterface;
use FSi\Component\DataSource\Field\Type\DateTimeTypeInterface;
use FSi\Component\DataSource\Field\Type\DateTypeInterface;
use FSi\Component\DataSource\Field\Type\EntityTypeInterface;
use FSi\Component\DataSource\Field\Type\NumberTypeInterface;
use FSi\Component\DataSource\Field\Type\TextTypeInterface;
use FSi\Component\DataSource\Field\Type\TimeTypeInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_merge;

class FormFieldExtension extends FieldAbstractExtension
{
    private FormStorage $formStorage;
    private TranslatorInterface $translator;

    public static function getExtendedFieldTypes(): array
    {
        return [
            TextTypeInterface::class,
            NumberTypeInterface::class,
            DateTypeInterface::class,
            TimeTypeInterface::class,
            DateTimeTypeInterface::class,
            EntityTypeInterface::class,
            BooleanTypeInterface::class,
        ];
    }

    public function __construct(FormStorage $formStorage, TranslatorInterface $translator)
    {
        $this->formStorage = $formStorage;
        $this->translator = $translator;
    }

    public function initOptions(OptionsResolver $optionsResolver, FieldTypeInterface $fieldType): void
    {
        $optionsResolver
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
            ->setNormalizer('form_type', static function (Options $options, ?string $type) use ($fieldType): ?string {
                if (null !== $type) {
                    return $type;
                }

                if ('isNull' === $options['comparison']) {
                    return ChoiceType::class;
                }

                if ($fieldType instanceof BooleanTypeInterface) {
                    return ChoiceType::class;
                }
                if ($fieldType instanceof TextTypeInterface) {
                    return TextType::class;
                }
                if ($fieldType instanceof NumberTypeInterface) {
                    return NumberType::class;
                }
                if ($fieldType instanceof DateTypeInterface) {
                    return DateType::class;
                }
                if ($fieldType instanceof TimeTypeInterface) {
                    return TimeType::class;
                }
                if ($fieldType instanceof DateTimeTypeInterface) {
                    return DateTimeType::class;
                }
                if ($fieldType instanceof EntityTypeInterface) {
                    return EntityType::class;
                }

                return null;
            })
            ->setNormalizer('form_options', function (Options $options, array $formOptions) use ($fieldType): array {
                if ('isNull' === $options['comparison'] && ChoiceType::class === $options['form_type']) {
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

                if ($fieldType instanceof BooleanTypeInterface && ChoiceType::class === $options['form_type']) {
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

    public function buildView(FieldInterface $field, FieldViewInterface $view): void
    {
        $form = $this->formStorage->getForm($field);
        if (null !== $form) {
            $view->setAttribute('form', $form->createView());
        }
    }
}
