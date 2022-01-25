<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Extension\Core;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Extension\Core\Ordering\EventSubscriber\OrderingPostGetParameters;
use FSi\Component\DataSource\Extension\Core\Ordering\EventSubscriber\OrderingPreBindParameters;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\Storage;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\TestCase;

use function array_merge;

final class OrderingExtensionTest extends TestCase
{
    public function testStoringParameters(): void
    {
        $datasource = $this->createMock(DataSourceInterface::class);
        $orderingStorage = new Storage();
        $field = $this->createMock(FieldInterface::class);

        $datasource->method('getFields')->willReturn(['test' => $field]);
        $datasource->method('getField')->with('test')->willReturn($field);
        $datasource->method('getName')->willReturn('ds');

        $preBindParametersSubscriber = new OrderingPreBindParameters($orderingStorage);
        $postGetParametersSubscriber = new OrderingPostGetParameters($orderingStorage);

        $parameters = ['ds' => [OrderingExtension::PARAMETER_SORT => ['test' => 'asc']]];
        ($preBindParametersSubscriber)(new DataSourceEvent\PreBindParameters($datasource, $parameters));

        // Assert that request parameters are properly stored in FieldExtension.
        self::assertEquals(0, $orderingStorage->getFieldSortingPriority($field));
        self::assertEquals(true, $orderingStorage->isFieldSortingAscending($field));

        $event = new DataSourceEvent\PostGetParameters($datasource, []);
        ($postGetParametersSubscriber)($event);

        self::assertEquals($parameters, $event->getParameters());
    }

    /**
     * Each test case consists of fields options definition, ordering parameters passed to datasource
     * and expected fields array which should be sorted in terms of priority of sorting results.
     * Expected array contain sorting passed in parameters first and then default sorting passed in options.
     *
     * @return array<int,array{
     *     fields: array<int,array<string,mixed>>,
     *     parameters: array<string,string>,
     *     expected_ordering: array<string,string>,
     *     expected_parameters: array<string,mixed>
     * }>
     */
    public function orderingDataProvider(): array
    {
        return [
            [
                'fields' => [
                    ['name' => 'field1'],
                    ['name' => 'field2'],
                    ['name' => 'field3'],
                ],
                'parameters' => [
                    'field1' => 'asc'
                ],
                'expected_ordering' => [
                    'field1' => 'asc',
                ],
                'expected_parameters' => [
                    'field1' => [
                        'ordering_ascending' => ['field1' => 'asc'],
                        'ordering_descending' => ['field1' => 'desc']
                    ],
                    'field2' => [
                        'ordering_ascending' => [
                            'field2' => 'asc',
                            'field1' => 'asc'
                        ],
                        'ordering_descending' => [
                            'field2' => 'desc',
                            'field1' => 'asc'
                        ]
                    ],
                    'field3' => [
                        'ordering_ascending' => [
                            'field3' => 'asc',
                            'field1' => 'asc'
                        ],
                        'ordering_descending' => [
                            'field3' => 'desc',
                            'field1' => 'asc'
                        ]
                    ],
                ]
            ],
            [
                'fields' => [
                    ['name' => 'field1'],
                    ['name' => 'field2'],
                    ['name' => 'field3'],
                ],
                'parameters' => [
                    'field2' => 'asc',
                    'field1' => 'desc',
                ],
                'expected_ordering' => [
                    'field2' => 'asc',
                    'field1' => 'desc'
                ],
                'expected_parameters' => [
                    'field1' => [
                        'ordering_ascending' => [
                            'field1' => 'asc',
                            'field2' => 'asc'
                        ],
                        'ordering_descending' => [
                            'field1' => 'desc',
                            'field2' => 'asc'
                        ]
                    ],
                    'field2' => [
                        'ordering_ascending' => [
                            'field2' => 'asc',
                            'field1' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field2' => 'desc',
                            'field1' => 'desc'
                        ]
                    ],
                    'field3' => [
                        'ordering_ascending' => [
                            'field3' => 'asc',
                            'field2' => 'asc',
                            'field1' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field3' => 'desc',
                            'field2' => 'asc',
                            'field1' => 'desc'
                        ]
                    ],
                ]
            ],
            [
                'fields' => [
                    [
                        'name' => 'field1',
                        'options' => ['default_sort' => 'asc', 'default_sort_priority' => 1]
                    ],
                    [
                        'name' => 'field2',
                        'options' => ['default_sort' => 'desc', 'default_sort_priority' => 2]
                    ],
                    [
                        'name' => 'field3',
                        'options' => ['default_sort' => 'asc']
                    ],
                ],
                'parameters' => ['field3' => 'desc'],
                'expected_ordering' => [
                    'field3' => 'desc',
                    'field2' => 'desc',
                    'field1' => 'asc'
                ],
                'expected_parameters' => [
                    'field1' => [
                        'ordering_ascending' => [
                            'field1' => 'asc',
                            'field3' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field1' => 'desc',
                            'field3' => 'desc'
                        ]
                    ],
                    'field2' => [
                        'ordering_ascending' => [
                            'field2' => 'asc',
                            'field3' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field2' => 'desc',
                            'field3' => 'desc'
                        ]
                    ],
                    'field3' => [
                        'ordering_ascending' => ['field3' => 'asc'],
                        'ordering_descending' => ['field3' => 'desc']
                    ],
                ]
            ],
            [
                'fields' => [
                    [
                        'name' => 'field1',
                        'options' => ['default_sort' => 'asc', 'default_sort_priority' => 1]
                    ],
                    [
                        'name' => 'field2',
                        'options' => ['default_sort' => 'desc', 'default_sort_priority' => 2]
                    ],
                    [
                        'name' => 'field3',
                        'options' => ['default_sort' => 'asc']
                    ],
                ],
                'parameters' => [
                    'field1' => 'asc',
                    'field3' => 'desc'
                ],
                'expected_ordering' => [
                    'field1' => 'asc',
                    'field3' => 'desc',
                    'field2' => 'desc'
                ],
                'expected_parameters' => [
                    'field1' => [
                        'ordering_ascending' => [
                            'field1' => 'asc',
                            'field3' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field1' => 'desc',
                            'field3' => 'desc'
                        ]
                    ],
                    'field2' => [
                        'ordering_ascending' => [
                            'field2' => 'asc',
                            'field1' => 'asc',
                            'field3' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field2' => 'desc',
                            'field1' => 'asc',
                            'field3' => 'desc'
                        ]
                    ],
                    'field3' => [
                        'ordering_ascending' => [
                            'field3' => 'asc',
                            'field1' => 'asc'
                        ],
                        'ordering_descending' => [
                            'field3' => 'desc',
                            'field1' => 'asc'
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Checks if sort order is properly calculated from default sorting options
     * and parameters passed from user request.
     *
     * @dataProvider orderingDataProvider
     * @param array<int,array<string,mixed>> $fields
     * @param array<string,string> $parameters
     * @param array<string,string> $expectedOrdering
     * @param array<string,mixed> $expectedParameters
     */
    public function testOrdering(
        array $fields,
        array $parameters,
        array $expectedOrdering,
        array $expectedParameters
    ): void {
        $dataSource = $this->createMock(DataSourceInterface::class);

        $storage = new Storage();
        $fieldExtension = new FieldExtension($storage);

        $dataSourceFields = [];
        foreach ($fields as $fieldData) {
            // Using fake class object instead of mock object is helpful
            // because we need functionality from AbstractFieldType.
            $fieldType = new FakeFieldType([$fieldExtension]);
            $field = $fieldType->createField(
                $dataSource,
                $fieldData['name'],
                array_merge($fieldData['options'] ?? [], ['comparison' => 'eq'])
            );
            $dataSourceFields[$fieldData['name']] = $field;
        }

        $dataSource->expects(self::atLeastOnce())->method('getName')->willReturn('ds');
        $dataSource->method('getFields')->willReturn($fields);

        $dataSource
            ->method('getField')
            ->willReturnCallback(fn() => $dataSourceFields[func_get_arg(0)])
        ;

        $dataSource
            ->method('getParameters')
            ->willReturn(['ds' => [OrderingExtension::PARAMETER_SORT => $parameters]])
        ;

        $preBindParametersSubscriber = new OrderingPreBindParameters($storage);

        ($preBindParametersSubscriber)(new DataSourceEvent\PreBindParameters(
            $dataSource,
            ['ds' => [OrderingExtension::PARAMETER_SORT => $parameters]]
        ));

        $result = $storage->sortFields($dataSourceFields);
        self::assertSame($expectedOrdering, $result);

        foreach ($dataSourceFields as $field) {
            $view = $this->createMock(FieldViewInterface::class);

            $view
                ->expects(self::exactly(5))
                ->method('setAttribute')
                ->willReturnCallback(
                    static function ($attribute, $value) use ($field, $parameters, $expectedParameters) {
                        switch ($attribute) {
                            case 'sorted_ascending':
                                self::assertEquals(
                                    (key($parameters) === $field->getName()) && (current($parameters) === 'asc'),
                                    $value
                                );
                                break;

                            case 'sorted_descending':
                                self::assertEquals(
                                    (key($parameters) === $field->getName()) && (current($parameters) === 'desc'),
                                    $value
                                );
                                break;

                            case 'parameters_sort_ascending':
                                self::assertSame(
                                    [
                                        'ds' => [
                                            OrderingExtension::PARAMETER_SORT
                                                => $expectedParameters[$field->getName()]['ordering_ascending']
                                        ]
                                    ],
                                    $value
                                );
                                break;

                            case 'parameters_sort_descending':
                                self::assertSame(
                                    [
                                        'ds' => [
                                            OrderingExtension::PARAMETER_SORT
                                                => $expectedParameters[$field->getName()]['ordering_descending']
                                        ]
                                    ],
                                    $value
                                );
                                break;
                        }
                    }
                )
            ;

            $fieldExtension->buildView($field, $view);
        }
    }
}
