<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Core\ColumnType;

use DateTime;
use DateTimeImmutable;
use FSi\Component\DataGrid\DataGridFactory;
use FSi\Component\DataGrid\DataGridFactoryInterface;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use FSi\Component\DataGrid\Extension\Core\ColumnType\DateTime as DateTimeColumnType;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Component\DataGrid\Fixtures\SimpleDataGridExtension;

class DateTimeTest extends TestCase
{
    private DataGridFactoryInterface $dataGridFactory;

    public function testDateTimeValue(): void
    {
        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            ['field_mapping' => ['datetime']]
        );

        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $cellView = $this->dataGridFactory->createCellView($column, (object) [
            'datetime' => $dateTimeObject,
        ]);

        $this->assertSame(
            ['datetime' => $dateTimeObject->format('Y-m-d H:i:s')],
            $cellView->getValue()
        );
    }

    public function testDateTimeImmutableValue(): void
    {
        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            ['field_mapping' => ['datetime']]
        );

        $dateTimeObject = new DateTimeImmutable('2012-05-03 12:41:11');
        $cellView = $this->dataGridFactory->createCellView($column, (object) [
            'datetime' => $dateTimeObject,
        ]);

        $this->assertSame(
            ['datetime' => $dateTimeObject->format('Y-m-d H:i:s')],
            $cellView->getValue()
        );
    }

    public function testNullValue(): void
    {
        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            ['field_mapping' => ['datetime']]
        );

        $cellView = $this->dataGridFactory->createCellView($column, (object) [
            'datetime' => null,
        ]);

        $this->assertSame(['datetime' => null], $cellView->getValue());

        $inputTypes = ['datetime', 'string', 'timestamp'];

        foreach ($inputTypes as $input_type) {
            $column = $this->dataGridFactory->createColumn(
                $this->getDataGridMock(),
                DateTimeColumnType::class,
                'datetime',
                [
                    'field_mapping' => ['datetime'],
                    'input_type' => $input_type,
                ]
            );

            $cellView = $this->dataGridFactory->createCellView($column, (object) [
                'datetime' => null,
            ]);

            $this->assertSame(['datetime' => null], $cellView->getValue());
        }
    }

    public function testFormatOption(): void
    {
        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime'],
                'datetime_format' => 'Y.d.m',
            ]
        );

        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $cellView = $this->dataGridFactory->createCellView($column, (object) [
            'datetime' => $dateTimeObject,
        ]);

        $this->assertSame(
            ['datetime' => $dateTimeObject->format('Y.d.m')],
            $cellView->getValue()
        );
    }

    public function testFormatOptionWithDateTimeImmutable(): void
    {
        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime'],
                'datetime_format' => 'Y.d.m',
            ]
        );

        $dateTimeObject = new DateTimeImmutable('2012-05-03 12:41:11');
        $cellView = $this->dataGridFactory->createCellView($column, (object) [
            'datetime' => $dateTimeObject,
        ]);

        $this->assertSame(
            ['datetime' => $dateTimeObject->format('Y.d.m')],
            $cellView->getValue()
        );
    }

    public function testTimestampValue(): void
    {
        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $brokenValue = (object) [
            'datetime' => $dateTimeObject
        ];
        $value = (object) [
            'datetime' => $dateTimeObject->getTimestamp()
        ];

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime'],
                'input_type' => 'timestamp',
            ]
        );

        $cellView = $this->dataGridFactory->createCellView($column, $value);

        $this->assertSame(
            ['datetime' => $dateTimeObject->format('Y-m-d H:i:s')],
            $cellView->getValue()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->dataGridFactory->createCellView($column, $brokenValue);
    }

    public function testStringValueWithMissingFieldsFormat(): void
    {
        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $value = (object) [
            'datetime' => $dateTimeObject->format('Y-m-d H:i:s')
        ];

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime'],
                'input_type' => 'string',
            ]
        );

        $this->expectException(DataGridColumnException::class);
        $this->dataGridFactory->createCellView($column, $value);
    }

    public function testStringValue(): void
    {
        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $brokenValue = (object) [
            'datetime' => $dateTimeObject
        ];
        $value = (object) [
            'datetime' => $dateTimeObject->format('Y-m-d H:i:s')
        ];

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime'],
                'input_field_format' => 'Y-m-d H:i:s',
                'input_type' => 'string',
            ]
        );
        $cellView = $this->dataGridFactory->createCellView($column, $value);

        $this->assertSame(
            ['datetime' => $dateTimeObject->format('Y-m-d H:i:s')],
            $cellView->getValue()
        );

        $this->expectException(DataGridColumnException::class);
        $this->dataGridFactory->createCellView($column, $brokenValue);
    }

    public function testArrayValueWithMissingFieldsFormat(): void
    {
        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $dateObject = new DateTime('2012-05-03');
        $value = (object) [
            'datetime' => $dateTimeObject->format('Y-m-d H:i:s'),
            'time' => $dateObject->format('Y-m-d H:i:s'),
        ];

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime', 'time'],
                'input_type' => 'array',
            ]
        );

        $this->expectException(DataGridColumnException::class);
        $this->dataGridFactory->createCellView($column, $value);
    }

    public function testArrayValueWithMissingFieldsFormatForDateTimeImmutable(): void
    {
        $dateTimeObject = new DateTimeImmutable('2012-05-03 12:41:11');
        $dateObject = new DateTimeImmutable('2012-05-03');
        $value = (object) [
            'datetime' => $dateTimeObject->format('Y-m-d H:i:s'),
            'time' => $dateObject->format('Y-m-d H:i:s'),
        ];

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime', 'time'],
                'input_type' => 'array',
            ]
        );

        $this->expectException(DataGridColumnException::class);
        $this->dataGridFactory->createCellView($column, $value);
    }

    public function testArrayValueWithWrongFieldsFormat(): void
    {
        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $dateObject = new DateTime('2012-05-03');
        $value = (object) [
            'datetime' => $dateTimeObject->format('Y-m-d H:i:s'),
            'time' => $dateObject->format('Y-m-d H:i:s'),
        ];

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime', 'time'],
                'input_type' => 'string',
                'input_field_format' => [
                    'datetime' => 'string',
                    'time' => 'string',
                ],
            ]
        );

        $this->expectException(DataGridColumnException::class);
        $this->dataGridFactory->createCellView($column, $value);
    }

    public function testArrayValue(): void
    {
        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $dateObject = new DateTime('2012-05-03');
        $dateTimeImmutableObject = new DateTimeImmutable('2012-05-03 12:41:11');
        $value = (object) [
            'datetime' => $dateTimeObject,
            'time' => $dateObject,
            'string' => $dateTimeObject->format('Y-m-d H:i:s'),
            'timestamp' => $dateTimeObject->getTimestamp(),
            'datetime_immutable' => $dateTimeImmutableObject,
        ];

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'field_mapping' => ['datetime', 'time', 'string', 'timestamp', 'datetime_immutable'],
                'input_type' => 'array',
                'input_field_format' => [
                    'datetime' => ['input_type' => 'datetime'],
                    'time' => ['input_type' => 'datetime'],
                    'string' => ['input_type' => 'string', 'datetime_format' => 'Y-m-d H:i:s'],
                    'timestamp' => ['input_type' => 'timestamp'],
                    'datetime_immutable' => ['input_type' => 'datetime']
                ],
            ]
        );
        $cellView = $this->dataGridFactory->createCellView($column, $value);

        $expectedResult = [
            'datetime' => $dateTimeObject->format('Y-m-d H:i:s'),
            'time' => $dateObject->format('Y-m-d 00:00:00'),
            'string' => $dateTimeObject->format('Y-m-d H:i:s'),
            'timestamp' => date('Y-m-d H:i:s', $dateTimeObject->getTimestamp()),
            'datetime_immutable' => $dateTimeImmutableObject->format('Y-m-d H:i:s')
        ];

        $this->assertSame($expectedResult, $cellView->getValue());
    }

    public function testArrayValueWithFormat(): void
    {
        $dateTimeObject = new DateTime('2012-05-03 12:41:11');
        $dateObject = new DateTime('2012-05-03');
        $dateTimeImmutableObject = new DateTimeImmutable('2012-05-03 12:41:11');
        $value = (object) [
            'datetime' => $dateTimeObject,
            'time' => $dateObject,
            'string' => $dateTimeObject->format('Y-m-d H:i:s'),
            'timestamp' => $dateTimeObject->getTimestamp(),
            'datetime_immutable' => $dateTimeImmutableObject,
        ];

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            DateTimeColumnType::class,
            'datetime',
            [
                'datetime_format' => 'Y.d.m',
                'field_mapping' => ['datetime', 'time', 'string', 'timestamp', 'datetime_immutable'],
                'input_type' => 'array',
                'input_field_format' => [
                    'datetime' => ['input_type' => 'datetime'],
                    'time' => ['input_type' => 'datetime'],
                    'string' => ['input_type' => 'string', 'datetime_format' => 'Y-m-d H:i:s'],
                    'timestamp' => ['input_type' => 'timestamp'],
                    'datetime_immutable' => ['input_type' => 'datetime'],
                ],
            ]
        );
        $cellView = $this->dataGridFactory->createCellView($column, $value);

        $expectedResult = [
            'datetime' => $dateTimeObject->format('Y.d.m'),
            'time' => $dateObject->format('Y.d.m'),
            'string' => $dateTimeObject->format('Y.d.m'),
            'timestamp' => $dateTimeObject->format('Y.d.m'),
            'datetime_immutable' => $dateTimeImmutableObject->format('Y.d.m'),
        ];

        $this->assertSame($expectedResult, $cellView->getValue());
    }

    protected function setUp(): void
    {
        $this->dataGridFactory = new DataGridFactory(
            [new SimpleDataGridExtension(new DefaultColumnOptionsExtension(), new DateTimeColumnType())],
            $this->createMock(DataMapperInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );
    }

    /**
     * @return DataGridInterface&MockObject
     */
    private function getDataGridMock(): DataGridInterface
    {
        $dataGrid = $this->createMock(DataGridInterface::class);
        $dataGrid->method('getDataMapper')
            ->willReturn(new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor()));

        return $dataGrid;
    }
}
