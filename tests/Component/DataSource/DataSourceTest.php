<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource;

use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\DataSourceFactoryInterface;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event\PostBindParameters;
use FSi\Component\DataSource\Event\PostBuildView;
use FSi\Component\DataSource\Event\PostGetParameters;
use FSi\Component\DataSource\Event\PreBindParameters;
use FSi\Component\DataSource\Event\PreBuildView;
use FSi\Component\DataSource\Event\PreGetParameters;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\Event\PostBindParameter;
use FSi\Component\DataSource\Field\Event\PostGetParameter;
use FSi\Component\DataSource\Field\Event\PreBindParameter;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\FieldTypeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tests\FSi\Component\DataSource\Fixtures\TestResult;

final class DataSourceTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testDataSourceCreate(): void
    {
        new DataSource(
            'datasource',
            $this->createMock(DataSourceFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createDriverMock()
        );
    }

    public function testDataSourceName(): void
    {
        $driver = $this->createDriverMock();
        $factory = $this->createMock(DataSourceFactoryInterface::class);

        $dataSource = new DataSource(
            'name1',
            $factory,
            $this->createMock(EventDispatcherInterface::class),
            $driver
        );
        self::assertEquals('name1', $dataSource->getName());
        self::assertSame($factory, $dataSource->getFactory());

        $dataSource = new DataSource(
            'name2',
            $this->createMock(DataSourceFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $driver
        );
        self::assertEquals('name2', $dataSource->getName());
    }

    public function testDataSourceExceptionOnWrongName(): void
    {
        $this->expectException(DataSourceException::class);
        new DataSource(
            'wrong-name',
            $this->createMock(DataSourceFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createDriverMock()
        );
    }

    public function testDataSourceCreatingAddingGettingDeletingFields(): void
    {
        $driver = $this->createDriverMock();
        $dataSource = new DataSource(
            'datasource',
            $this->createMock(DataSourceFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $driver
        );

        $field = $this->createMock(FieldInterface::class);
        $field->method('getName')->willReturn('name1');
        $field->method('getDataSource')->willReturn($dataSource);
        $fieldType = $this->createMock(FieldTypeInterface::class);
        $fieldType->method('createField')->with($dataSource, self::anything(), self::anything())->willReturn($field);

        $driver->method('getFieldType')->with('text')->willReturn($fieldType);

        $dataSource->addField('name1', 'text', ['comparison' => 'comp1']);

        self::assertCount(1, $dataSource->getFields());
        self::assertTrue($dataSource->hasField('name1'));
        self::assertFalse($dataSource->hasField('wrong'));

        $dataSource->clearFields();
        self::assertCount(0, $dataSource->getFields());

        $dataSource->addField('name1', 'text', ['comparison' => 'comp1']);
        self::assertCount(1, $dataSource->getFields());
        self::assertFalse($dataSource->hasField('name'));
        self::assertTrue($dataSource->hasField('name1'));
        self::assertFalse($dataSource->hasField('name2'));

        self::assertEquals($field, $dataSource->getField('name1'));

        $dataSource->removeField('name1');
        self::assertCount(0, $dataSource->getFields());
        $dataSource->removeField('name');

        $this->expectException(DataSourceException::class);
        $dataSource->getField('wrong');
    }

    public function testParametersBindingException(): void
    {
        $dataSource = new DataSource(
            'datasource',
            $this->createMock(DataSourceFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createDriverMock()
        );
        $dataSource->bindParameters([]);
        $this->expectException(DataSourceException::class);
        $dataSource->bindParameters('nonarray');
    }

    public function testBindAndGetResult(): void
    {
        $field = $this->createMock(FieldInterface::class);
        $field->method('getName')->willReturn('field');
        $field->expects(self::exactly(2))->method('bindParameter');

        $fieldType = $this->createMock(FieldTypeInterface::class);
        $fieldType->method('createField')->willReturn($field);

        $driver = $this->createDriverMock();
        $driver->method('getFieldType')->with('type')->willReturn($fieldType);

        $dataSource = new DataSource(
            'datasource',
            $this->createMock(DataSourceFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $driver
        );
        $field->method('getDataSource')->willReturn($dataSource);

        $testResult = new TestResult();

        $firstData = [
            'datasource' => [
                'fields' => [
                    'field' => 'value',
                    'other' => 'notimportant'
                ],
            ],
        ];
        $secondData = [
            'datasource' => [
                'fields' => ['somefield' => 'somevalue'],
            ],
        ];


        $driver->expects(self::once())->method('getResult')->with(['field' => $field])->willReturn($testResult);

        $dataSource->addField('field', 'type', ['comparison' => 'eq']);
        $dataSource->bindParameters($firstData);
        $dataSource->bindParameters($secondData);

        $dataSource->getResult();
    }

    public function testPaginationParametersForwardingToDriver(): void
    {
        $datasource = new DataSource(
            'datasource',
            $this->createMock(DataSourceFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createDriverMock()
        );

        $datasource->setMaxResults(20);
        $datasource->setFirstResult(40);

        self::assertEquals(20, $datasource->getMaxResults());
        self::assertEquals(40, $datasource->getFirstResult());
    }

    public function testPreAndPostGetParametersCalls(): void
    {
        $field = $this->createMock(FieldInterface::class);
        $field2 = $this->createMock(FieldInterface::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $fieldType = $this->createMock(FieldTypeInterface::class);

        $driver = $this->createDriverMock();
        $driver->method('getFieldType')->willReturn($fieldType);

        $dataSource = new DataSource(
            'datasource',
            $this->createMock(DataSourceFactoryInterface::class),
            $eventDispatcher,
            $driver
        );

        $field->method('getType')->willReturn($fieldType);
        $field->method('getName')->willReturn('key');
        $field->method('getParameter')->willReturn('a');
        $field->method('getDataSource')->willReturn($dataSource);

        $field2->method('getType')->willReturn($fieldType);
        $field2->method('getName')->willReturn('key2');
        $field2->method('getParameter')->willReturn('b');
        $field2->method('getDataSource')->willReturn($dataSource);

        $fieldType->method('createField')
            ->withConsecutive(
                [self::isInstanceOf(DataSourceInterface::class), 'field', []],
                [self::isInstanceOf(DataSourceInterface::class), 'field2', []]
            )
            ->willReturnOnConsecutiveCalls($field, $field2)
        ;

        $eventDispatcher->expects(self::exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(PreGetParameters::class)],
                [self::isInstanceOf(PostGetParameter::class)],
                [self::isInstanceOf(PostGetParameter::class)],
                [self::isInstanceOf(PostGetParameters::class)],
            )
        ;

        $dataSource->addField('field', 'type', []);
        $dataSource->addField('field2', 'type', []);
        self::assertEquals(['datasource' => ['fields' => ['key' => 'a', 'key2' => 'b']]], $dataSource->getParameters());
    }

    public function testViewCreation(): void
    {
        $driver = $this->createDriverMock();

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(PreBuildView::class)],
                [self::isInstanceOf(PreGetParameters::class)],
                [self::isInstanceOf(PostGetParameters::class)],
                [self::isInstanceOf(PostBuildView::class)]
            );

        $datasource = new DataSource(
            'datasource',
            $this->createMock(DataSourceFactoryInterface::class),
            $eventDispatcher,
            $driver
        );
        $view = $datasource->createView();
        self::assertEquals('datasource', $view->getName());
    }

    public function testGetAllAndOthersParameters(): void
    {
        $factory = $this->createMock(DataSourceFactoryInterface::class);

        $datasource = new DataSource(
            'datasource',
            $factory,
            $this->createMock(EventDispatcherInterface::class),
            $this->createDriverMock()
        );

        $factory->expects(self::once())->method('getOtherParameters')->with($datasource)->willReturn(['a' => 'b']);
        $factory->expects(self::once())->method('getAllParameters')->willReturn(['c' => 'd']);

        self::assertEquals(['a' => 'b'], $datasource->getOtherParameters());
        self::assertEquals(['c' => 'd'], $datasource->getAllParameters());
    }

    public function testExtensionsCallsDuringBindParameters(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $driver = $this->createDriverMock();

        $dataSource = new DataSource(
            'datasource',
            $this->createMock(DataSourceFactoryInterface::class),
            $eventDispatcher,
            $driver
        );

        $testResult = new TestResult();
        $driver->method('getResult')->willReturn($testResult);

        $dataSource->addField('field', 'text', ['comparison' => 'eq']);

        $eventDispatcher->expects(self::exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(PreBindParameters::class)],
                [self::isInstanceOf(PreBindParameter::class)],
                [self::isInstanceOf(PostBindParameter::class)],
                [self::isInstanceOf(PostBindParameters::class)],
            );
        $dataSource->bindParameters(['datasource' => []]);
    }

    /**
     * @return DriverInterface&MockObject
     */
    private function createDriverMock(): MockObject
    {
        return $this->createMock(DriverInterface::class);
    }
}
