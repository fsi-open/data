<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\FSi\Bundle\DataSourceBundle\Extension\Symfony;

use DateTimeImmutable;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Driver\DriverExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\EventSubscriber\Events;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Extension\DatasourceExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field\FormFieldExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Type\BetweenType;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\Form as TestForm;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Event\DataSourceEvent\ViewEventArgs;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Exception\DataSourceException;
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

class FormExtensionTest extends TestCase
{
    public static function typesProvider(): array
    {
        return [
            ['text'],
            ['number'],
            ['date'],
            ['time'],
            ['datetime']
        ];
    }

    /**
     * Provides field types, comparison types and expected form input types.
     *
     * @return array
     */
    public static function fieldTypesProvider(): array
    {
        return [
            ['text', 'isNull', 'choice'],
            ['text', 'eq', 'text'],
            ['number', 'isNull', 'choice'],
            ['number', 'eq', 'number'],
            ['datetime', 'isNull', 'choice'],
            ['datetime', 'eq', 'datetime'],
            ['datetime', 'between', 'datasource_between'],
            ['time', 'isNull', 'choice'],
            ['time', 'eq', 'time'],
            ['date', 'isNull', 'choice'],
            ['date', 'eq', 'date']
        ];
    }

    /**
     * Checks creation of DriverExtension.
     */
    public function testCreateDriverExtension(): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);

        $driver = new DriverExtension($formFactory, $translator);
        // Without an assertion the test would be marked as risky
        self::assertNotNull($driver);
    }

    /**
     * Tests if driver extension has all needed fields.
     */
    public function testDriverExtension(): void
    {
        $this->expectException(DataSourceException::class);

        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);
        $extension = new DriverExtension($formFactory, $translator);

        self::assertTrue($extension->hasFieldTypeExtensions('text'));
        self::assertTrue($extension->hasFieldTypeExtensions('number'));
        self::assertTrue($extension->hasFieldTypeExtensions('entity'));
        self::assertTrue($extension->hasFieldTypeExtensions('date'));
        self::assertTrue($extension->hasFieldTypeExtensions('time'));
        self::assertTrue($extension->hasFieldTypeExtensions('datetime'));
        self::assertFalse($extension->hasFieldTypeExtensions('wrong'));

        $extension->getFieldTypeExtensions('text');
        $extension->getFieldTypeExtensions('number');
        $extension->getFieldTypeExtensions('entity');
        $extension->getFieldTypeExtensions('date');
        $extension->getFieldTypeExtensions('time');
        $extension->getFieldTypeExtensions('datetime');
        $extension->getFieldTypeExtensions('wrong');
    }

    public function testFormOrder(): void
    {
        $datasource = $this->createMock(DataSourceInterface::class);
        $view = $this->createMock(DataSourceViewInterface::class);

        $fields = [];
        $fieldViews = [];
        for ($i = 0; $i < 15; $i++) {
            $field = $this->createMock(FieldTypeInterface::class);
            $fieldView = $this->createMock(FieldViewInterface::class);

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

            $fieldView->method('getName')->willReturn('field' . $i);
            $fields['field' . $i] = $field;
            $fieldViews['field' . $i] = $fieldView;
            if (isset($order)) {
                $names['field' . $i] = $order;
            } else {
                $names['field' . $i] = null;
            }
        }

        $datasource
            ->method('getField')
            ->willReturnCallback(
                static function ($field) use ($fields) {
                    return $fields[$field];
                }
            )
        ;

        $view->method('getFields')->willReturn($fieldViews);
        $view
            ->expects(self::once())
            ->method('setFields')
            ->willReturnCallback(
                function (array $fields) {
                    $names = [];
                    foreach ($fields as $field) {
                        $names[] = $field->getName();
                    }

                    $this->assertSame(
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
                        $names
                    );
                }
            )
        ;

        $event = new ViewEventArgs($datasource, $view);
        $subscriber = new Events();
        $subscriber->postBuildView($event);
    }

    /**
     * @dataProvider typesProvider()
     */
    public function testFields(string $type): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);
        $extension = new DriverExtension($formFactory, $translator);
        $datasource = $this->createMock(DataSourceInterface::class);
        $datasource->method('getName')->willReturn('datasource');

        if ($type === 'datetime') {
            $parameters = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => [
                            'date' => ['year' => 2012, 'month' => 12, 'day' => 12],
                            'time' => ['hour' => 12, 'minute' => 12],
                        ]
                    ]
                ]
            ];
            $parameters2 = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => new DateTimeImmutable('2012-12-12 12:12:00')
                    ]
                ]
            ];
        } elseif ($type === 'time') {
            $parameters = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => ['hour' => 12, 'minute' => 12]
                    ]
                ]
            ];
            $parameters2 = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => new DateTimeImmutable(date('Y-m-d', 0) . ' 12:12:00')
                    ]
                ]
            ];
        } elseif ($type === 'date') {
            $parameters = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => ['year' => 2012, 'month' => 12, 'day' => 12]
                    ]
                ]
            ];
            $parameters2 = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => new DateTimeImmutable('2012-12-12')
                    ]
                ]
            ];
        } elseif ($type === 'number') {
            $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 123]]];
            $parameters2 = $parameters;
        } else {
            $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'value']]];
            $parameters2 = $parameters;
        }

        $field = new TestForm\Extension\TestCore\TestFieldType($datasource, $type, 'eq');
        $args = new FieldEvent\ParameterEventArgs($field, $parameters);

        $extensions = $extension->getFieldTypeExtensions($type);
        foreach ($extensions as $ext) {
            self::assertInstanceOf(FormFieldExtension::class, $ext);
            $field->addExtension($ext);
            $field->setOptions([
                'form_options' => true === in_array($type, ['date', 'datetime'], true)
                    ? ['years' => range(2012, (int) date('Y'))]
                    : [],
            ]);
            $ext->preBindParameter($args);
        }

        self::assertEquals($parameters2, $args->getParameter());
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

        $args = new FieldEvent\ViewEventArgs($field, $fieldView);
        foreach ($extensions as $ext) {
            self::assertInstanceOf(FormFieldExtension::class, $ext);
            $ext->postBuildView($args);
        }
    }

    /**
     * @dataProvider fieldTypesProvider
     */
    public function testFormFields(string $type, string $comparison, string $expected): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $extension = new DriverExtension($formFactory, $translator);
        $datasource = $this->createMock(DataSourceInterface::class);
        $datasource->method('getName')->willReturn('datasource');

        $field = new TestForm\Extension\TestCore\TestFieldType($datasource, $type, $comparison);

        $extensions = $extension->getFieldTypeExtensions($type);

        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]];
        $args = new FieldEvent\ParameterEventArgs($field, $parameters);

        $view = new FieldView($field);
        $viewEventArgs = new FieldEvent\ViewEventArgs($field, $view);

        foreach ($extensions as $ext) {
            self::assertInstanceOf(FormFieldExtension::class, $ext);
            $field->addExtension($ext);
            $ext->preBindParameter($args);
            $ext->postBuildView($viewEventArgs);
        }

        $form = $viewEventArgs->getView()->getAttribute('form');

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
        $formFieldExtension = new FormFieldExtension($formFactory, $translator);
        $datasource = $this->createMock(DataSourceInterface::class);
        $datasource->method('getName')->willReturn('datasource');

        $field = new TestForm\Extension\TestCore\TestFieldType($datasource, 'boolean', 'eq');
        $field->addExtension($formFieldExtension);
        $field->setOptions([
            'form_options' => ['choices' => ['tak' => '1', 'nie' => '0']]
        ]);

        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]];
        $args = new FieldEvent\ParameterEventArgs($field, $parameters);

        $view = new FieldView($field);
        $viewEventArgs = new FieldEvent\ViewEventArgs($field, $view);

        $formFieldExtension->preBindParameter($args);
        $formFieldExtension->postBuildView($viewEventArgs);

        $form = $viewEventArgs->getView()->getAttribute('form');
        $choices = $form['fields']['name']->vars['choices'];
        self::assertEquals('1', $choices[0]->value);
        self::assertEquals('tak', $choices[0]->label);
        self::assertEquals('0', $choices[1]->value);
        self::assertEquals('nie', $choices[1]->label);
    }

    public function testBuildBooleanFormWhenOptionsNotProvided(): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $formFieldExtension = new FormFieldExtension($formFactory, $translator);
        $datasource = $this->createMock(DataSourceInterface::class);
        $datasource->method('getName')->willReturn('datasource');

        $field = new TestForm\Extension\TestCore\TestFieldType($datasource, 'boolean', 'eq');
        $field->addExtension($formFieldExtension);

        $args = new FieldEvent\ParameterEventArgs(
            $field,
            ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]]
        );

        $view = new FieldView($field);
        $viewEventArgs = new FieldEvent\ViewEventArgs($field, $view);

        $formFieldExtension->preBindParameter($args);
        $formFieldExtension->postBuildView($viewEventArgs);

        $form = $viewEventArgs->getView()->getAttribute('form');
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
        string $comparison = 'eq'
    ): void {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $formFieldExtension = new FormFieldExtension($formFactory, $translator);
        $datasource = $this->createMock(DataSourceInterface::class);
        $datasource->method('getName')->willReturn('datasource');

        $field = new TestForm\Extension\TestCore\TestFieldType($datasource, $dataSourceFieldType, $comparison);
        $field->addExtension($formFieldExtension);
        $field->setOptions([
            'form_type' => HiddenType::class
        ]);

        $args = new FieldEvent\ParameterEventArgs(
            $field,
            ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]]
        );

        $view = new FieldView($field);
        $viewEventArgs = new FieldEvent\ViewEventArgs($field, $view);

        $formFieldExtension->preBindParameter($args);
        $formFieldExtension->postBuildView($viewEventArgs);

        $form = $viewEventArgs->getView()->getAttribute('form');
        self::assertEquals('hidden', $form['fields']['name']->vars['block_prefixes'][1]);
    }

    public function getDatasourceFieldTypes(): array
    {
        return [
            ['text', 'isNull'],
            ['text'],
            ['number'],
            ['date'],
            ['time'],
            ['datetime'],
            ['boolean']
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
                new Form\Extension\Csrf\CsrfExtension(new CsrfTokenManager()),
                new DatasourceExtension()
            ],
            $typeFactory
        );

        return new Form\FormFactory($registry);
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(
                static function ($id, array $params, $translation_domain): string {
                    if ($translation_domain !== 'DataSourceBundle') {
                        throw new RuntimeException(sprintf('Unknown translation domain %s', $translation_domain));
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
                            throw new RuntimeException(sprintf('Unknown translation id %s', $id));
                    }
                }
            );

        return $translator;
    }
}
