<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Extension\Symfony;

use DateTimeImmutable;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\EventSubscriber\DataSourcePostBuildView;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\EventSubscriber\FieldPreBindParameter;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Extension\DatasourceExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field\FormFieldExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\FormStorage;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Type\BetweenType;
use FSi\Component\DataSource\Event\DataSourceEvent\PreBuildView;
use FSi\Component\DataSource\Field\Field;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\BooleanTypeInterface;
use FSi\Component\DataSource\Field\Type\DateTimeTypeInterface;
use FSi\Component\DataSource\Field\Type\DateTypeInterface;
use FSi\Component\DataSource\Field\Type\NumberTypeInterface;
use FSi\Component\DataSource\Field\Type\TextTypeInterface;
use FSi\Component\DataSource\Field\Type\TimeTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\Form as TestForm;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldView;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Form;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function date;
use function in_array;
use function range;

final class FormExtensionTest extends TestCase
{
    /**
     * @return array<array{class-string<FieldTypeInterface>}>
     */
    public static function typesProvider(): array
    {
        return [
            [TextTypeInterface::class],
            [NumberTypeInterface::class],
            [DateTypeInterface::class],
            [TimeTypeInterface::class],
            [DateTimeTypeInterface::class],
        ];
    }

    /**
     * Provides field types, comparison types and expected form input types.
     *
     * @return array<array{class-string<FieldTypeInterface>, string, string}>
     */
    public static function fieldTypesProvider(): array
    {
        return [
            [TextTypeInterface::class, 'isNull', 'choice'],
            [TextTypeInterface::class, 'eq', 'text'],
            [NumberTypeInterface::class, 'isNull', 'choice'],
            [NumberTypeInterface::class, 'eq', 'number'],
            [DateTimeTypeInterface::class, 'isNull', 'choice'],
            [DateTimeTypeInterface::class, 'eq', 'datetime'],
            [DateTimeTypeInterface::class, 'between', 'datasource_between'],
            [TimeTypeInterface::class, 'isNull', 'choice'],
            [TimeTypeInterface::class, 'eq', 'time'],
            [DateTypeInterface::class, 'isNull', 'choice'],
            [DateTypeInterface::class, 'eq', 'date']
        ];
    }

    public function testFormOrder(): void
    {
        $dataSource = $this->createMock(DataSourceInterface::class);

        $fields = [];
        for ($i = 0; $i < 15; $i++) {
            $field = $this->createMock(FieldInterface::class);

            unset($order);
            if ($i < 5) {
                $order = -4 + $i;
            } elseif ($i > 10) {
                $order = $i - 10;
            }

            $field->method('getName')->willReturn('field' . $i);
            $field->method('hasOption')->willReturn(isset($order));

            if (isset($order)) {
                $field->method('getOption')->willReturn($order);
            }

            $fields['field' . $i] = $field;
        }

        $dataSource
            ->method('getField')
            ->willReturnCallback(
                static function ($field) use ($fields) {
                    return $fields[$field];
                }
            )
        ;

        $event = new PreBuildView($fields);
        $subscriber = new DataSourcePostBuildView();
        $subscriber($event);

        $sortedFields = array_map(static fn(FieldInterface $field): string => $field->getName(), $event->getFields());
        self::assertSame(
            [
                'field0',
                'field1',
                'field2',
                'field3',
                'field5',
                'field6',
                'field7',
                'field8',
                'field9',
                'field10',
                'field4',
                'field11',
                'field12',
                'field13',
                'field14'
            ],
            $sortedFields
        );
    }

    /**
     * @dataProvider typesProvider()
     * @param class-string<FieldTypeInterface> $type
     */
    public function testFields(string $type): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);
        $formStorage = new FormStorage($formFactory);
        $fieldExtension = new FormFieldExtension($formStorage, $translator);
        $preBindParametersSubscriber = new FieldPreBindParameter($formStorage);
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('datasource');

        if ($type === DateTimeTypeInterface::class) {
            $parameters = [
                'date' => ['year' => 2012, 'month' => 12, 'day' => 12],
                'time' => ['hour' => 12, 'minute' => 12],
            ];
            $parameters2 = new DateTimeImmutable('2012-12-12 12:12:00');
        } elseif ($type === TimeTypeInterface::class) {
            $parameters = ['hour' => 12, 'minute' => 12];
            $parameters2 = new DateTimeImmutable(date('Y-m-d', 0) . ' 12:12:00');
        } elseif ($type === DateTypeInterface::class) {
            $parameters = ['year' => 2012, 'month' => 12, 'day' => 12];
            $parameters2 = new DateTimeImmutable('2012-12-12');
        } elseif ($type === NumberTypeInterface::class) {
            $parameters = 123;
            $parameters2 = 123;
        } else {
            $parameters = 'value';
            $parameters2 = 'value';
        }

        $fieldType = $this->createMock($type);
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');
        $optionsResolver->setDefault('name', 'name');
        $optionsResolver->setRequired('comparison');
        $optionsResolver->setAllowedTypes('comparison', 'string');
        $fieldExtension->initOptions($optionsResolver, $fieldType);

        $options = $optionsResolver->resolve([
            'comparison' => 'eq',
            'form_options' => true === in_array($type, [DateTypeInterface::class, DateTimeTypeInterface::class], true)
                ? ['years' => range(2012, (int) date('Y'))]
                : []
        ]);

        $field = new Field($dataSource, $fieldType, 'name', $options);

        $event = new FieldEvent\PreBindParameter($field, $parameters);
        ($preBindParametersSubscriber)($event);

        self::assertEquals($parameters2, $event->getParameter());
        $fieldView = $this->getMockBuilder(FieldViewInterface::class)
            ->setConstructorArgs([$field])
            ->getMock()
        ;

        $fieldView
            ->expects(self::atLeastOnce())
            ->method('setAttribute')
            ->willReturnCallback(
                static function (string $attribute, $value): void {
                    if ($attribute === 'form') {
                        self::assertInstanceOf(FormView::class, $value);
                    }
                }
            )
        ;

        $fieldExtension->buildView($field, $fieldView);
    }

    /**
     * @dataProvider fieldTypesProvider
     * @param class-string<FieldTypeInterface> $type
     * @param string $comparison
     * @param string $expected
     */
    public function testFormFields(string $type, string $comparison, string $expected): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $formStorage = new FormStorage($formFactory);
        $fieldExtension = new FormFieldExtension($formStorage, $translator);
        $preBindParametersSubscriber = new FieldPreBindParameter($formStorage);
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('datasource');

        $fieldType = $this->createMock($type);
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');
        $optionsResolver->setDefault('name', 'name');
        $optionsResolver->setRequired('comparison');
        $optionsResolver->setAllowedTypes('comparison', 'string');
        $fieldExtension->initOptions($optionsResolver, $fieldType);

        $isDate = true === in_array($type, [DateTypeInterface::class, DateTimeTypeInterface::class], true)
            && false === in_array($comparison, ['isNull', 'between'], true);
        $options = $optionsResolver->resolve([
            'comparison' => $comparison,
            'form_options' => $isDate ? ['years' => range(2012, (int) date('Y'))] : [],
        ]);
        $field = new Field($dataSource, $fieldType, 'name', $options);

        $event = new FieldEvent\PreBindParameter($field, 'null');
        ($preBindParametersSubscriber)($event);

        $view = new FieldView($field);
        $fieldExtension->buildView($field, $view);

        $form = $view->getAttribute('form');
        self::assertEquals($expected, $form['fields']['name']->vars['block_prefixes'][1]);

        if ('isNull' === $comparison) {
            self::assertEquals(
                'is_null_translated',
                $form['fields']['name']->vars['choices'][0]->label
            );
            self::assertEquals(
                'is_not_null_translated',
                $form['fields']['name']->vars['choices'][1]->label
            );
        }
    }

    public function testBuildBooleanFormWhenOptionsProvided(): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $formStorage = new FormStorage($formFactory);
        $fieldExtension = new FormFieldExtension($formStorage, $translator);
        $preBindParametersSubscriber = new FieldPreBindParameter($formStorage);
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('datasource');

        $fieldType = $this->createMock(BooleanTypeInterface::class);
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');
        $optionsResolver->setDefault('name', 'name');
        $optionsResolver->setRequired('comparison');
        $optionsResolver->setAllowedTypes('comparison', 'string');
        $fieldExtension->initOptions($optionsResolver, $fieldType);
        $options = [
            'comparison' => 'eq',
            'form_options' => ['choices' => ['tak' => '1', 'nie' => '0']]
        ];

        $field = new Field($dataSource, $fieldType, 'name', $optionsResolver->resolve($options));
        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]];
        $event = new FieldEvent\PreBindParameter($field, $parameters);
        ($preBindParametersSubscriber)($event);

        $view = new FieldView($field);
        $fieldExtension->buildView($field, $view);

        $form = $view->getAttribute('form');
        $choices = $form['fields']['name']->vars['choices'];
        self::assertEquals('1', $choices[0]->value);
        self::assertEquals('tak', $choices[0]->label);
        self::assertEquals('0', $choices[1]->value);
        self::assertEquals('nie', $choices[1]->label);
    }

    public function testBuildBooleanFormWhenOptionsNotProvided(): void
    {
        $formFactory = $this->getFormFactory();
        $formStorage = new FormStorage($formFactory);
        $translator = $this->getTranslator();
        $fieldExtension = new FormFieldExtension($formStorage, $translator);
        $preBindParametersSubscriber = new FieldPreBindParameter($formStorage);
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('datasource');

        $fieldType = $this->createMock(BooleanTypeInterface::class);
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');
        $optionsResolver->setDefault('name', 'name');
        $optionsResolver->setRequired('comparison');
        $optionsResolver->setAllowedTypes('comparison', 'string');
        $fieldExtension->initOptions($optionsResolver, $fieldType);

        $field = new Field($dataSource, $fieldType, 'name', $optionsResolver->resolve(['comparison' => 'eq']));
        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]];
        $event = new FieldEvent\PreBindParameter($field, $parameters);
        ($preBindParametersSubscriber)($event);

        $view = new FieldView($field);
        $fieldExtension->buildView($field, $view);

        $form = $view->getAttribute('form');
        $choices = $form['fields']['name']->vars['choices'];
        self::assertEquals('1', $choices[0]->value);
        self::assertEquals('yes_translated', $choices[0]->label);
        self::assertEquals('0', $choices[1]->value);
        self::assertEquals('no_translated', $choices[1]->label);
    }

    /**
     * @dataProvider getDatasourceFieldTypes
     */
    public function testCreateDataSourceFieldWithCustomFormType(
        string $dataSourceFieldType,
        ?string $comparison
    ): void {
        $formFactory = $this->getFormFactory();
        $formStorage = new FormStorage($formFactory);
        $translator = $this->getTranslator();
        $fieldExtension = new FormFieldExtension($formStorage, $translator);
        $preBindParametersSubscriber = new FieldPreBindParameter($formStorage);
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('datasource');

        $fieldType = $this->createMock(BooleanTypeInterface::class);
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');
        $optionsResolver->setDefault('name', 'name');
        $optionsResolver->setRequired('comparison');
        $optionsResolver->setAllowedTypes('comparison', 'string');
        $fieldExtension->initOptions($optionsResolver, $fieldType);

        $options = ['comparison' => $comparison ?? 'eq', 'form_type' => HiddenType::class];
        $field = new Field($dataSource, $fieldType, 'name', $optionsResolver->resolve($options));

        $event = new FieldEvent\PreBindParameter(
            $field,
            ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]]
        );
        ($preBindParametersSubscriber)($event);

        $view = new FieldView($field);
        $fieldExtension->buildView($field, $view);

        $form = $view->getAttribute('form');
        self::assertEquals('hidden', $form['fields']['name']->vars['block_prefixes'][1]);
    }

    /**
     * @return array<array{class-string<FieldTypeInterface>,?string}>
     */
    public function getDatasourceFieldTypes(): array
    {
        return [
            [TextTypeInterface::class, 'isNull'],
            [TextTypeInterface::class, null],
            [NumberTypeInterface::class, null],
            [DateTypeInterface::class, null],
            [TimeTypeInterface::class, null],
            [DateTimeTypeInterface::class, null],
            [BooleanTypeInterface::class, null],
        ];
    }

    private function getFormFactory(): FormFactoryInterface
    {
        $typeFactory = new Form\ResolvedFormTypeFactory();
        $typeFactory->createResolvedType(new BetweenType(), []);

        $registry = new Form\FormRegistry(
            [
                new TestForm\Extension\TestCore\TestCoreExtension(),
                new Form\Extension\Core\CoreExtension(),
                new Form\Extension\Csrf\CsrfExtension(new CsrfTokenManager())
            ],
            $typeFactory
        );

        return new Form\FormFactory($registry);
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function getTranslator(): MockObject
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(
                static function (string $id, array $params, $translationDomain): string {
                    if ($translationDomain !== 'DataSourceBundle') {
                        throw new RuntimeException("Unknown translation domain {$translationDomain}");
                    }

                    switch ($id) {
                        case 'datasource.form.choices.is_null':
                            return 'is_null_translated';
                        case 'datasource.form.choices.is_not_null':
                            return 'is_not_null_translated';
                        case 'datasource.form.choices.yes':
                            return 'yes_translated';
                        case 'datasource.form.choices.no':
                            return 'no_translated';
                        default:
                            throw new RuntimeException("Unknown translation id {$id}");
                    }
                }
            );

        return $translator;
    }
}
