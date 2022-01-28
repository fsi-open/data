<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource;

use FSi\Component\DataSource\DataSourceView;
use FSi\Component\DataSource\Exception\DataSourceViewException;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\TestCase;

final class DataSourceViewTest extends TestCase
{
    public function testGetParameters(): void
    {
        $view = new DataSourceView('datasource', [], ['datasource' => []], ['other_datasource' => []]);
        self::assertEquals(['datasource' => []], $view->getParameters());
        self::assertEquals(['other_datasource' => []], $view->getOtherParameters());

        $allParameters = $view->getAllParameters();
        self::assertEquals(
            ['datasource' => [], 'other_datasource' => []],
            $allParameters
        );
    }

    public function testOptionsManipulation(): void
    {
        $view = new DataSourceView('datasource', [], [], []);

        self::assertFalse($view->hasAttribute('option1'));
        $view->setAttribute('option1', 'value1');
        self::assertTrue($view->hasAttribute('option1'));
        self::assertEquals('value1', $view->getAttribute('option1'));
        $view->removeAttribute('option1');
        self::assertFalse($view->hasAttribute('option1'));

        $view->setAttribute('option2', '');
        self::assertTrue($view->hasAttribute('option2'));

        $view->setAttribute('option2', null);
        self::assertTrue($view->hasAttribute('option2'));

        $view->setAttribute('option3', 'value3');
        $view->setAttribute('option4', 'value4');

        self::assertEquals(['option2' => null, 'option3' => 'value3', 'option4' => 'value4'], $view->getAttributes());

        self::assertEquals(null, $view->getAttribute('option2'));
    }

    public function testFieldsManipulation(): void
    {
        $fieldView1 = $this->createMock(FieldViewInterface::class);
        $fieldView1->method('getName')->willReturn('name1');
        $fieldType1 = $this->createMock(FieldTypeInterface::class);
        $fieldType1->method('createView')->willReturn($fieldView1);
        $field1 = $this->createMock(FieldInterface::class);
        $field1->method('getType')->willReturn($fieldType1);

        $fieldView2 = $this->createMock(FieldViewInterface::class);
        $fieldView2->method('getName')->willReturn('name2');
        $fieldType2 = $this->createMock(FieldTypeInterface::class);
        $fieldType2->method('createView')->willReturn($fieldView2);
        $field2 = $this->createMock(FieldInterface::class);
        $field2->method('getType')->willReturn($fieldType2);

        $view = new DataSourceView('datasource', [$field1, $field2], [], []);

        self::assertCount(2, $view);
        self::assertTrue(isset($view['name1']));
        self::assertTrue(isset($view['name2']));
        self::assertFalse(isset($view['wrong']));

        // Should be no exception thrown.
        self::assertSame($fieldView1, $view['name1']);
        self::assertSame($fieldView2, $view['name2']);
    }

    public function testExceptionWhenAddingAFieldWithTheSameName(): void
    {
        $fieldView = $this->createMock(FieldViewInterface::class);
        $fieldView->method('getName')->willReturn('name');
        $fieldType = $this->createMock(FieldTypeInterface::class);
        $fieldType->method('createView')->willReturn($fieldView);
        $field = $this->createMock(FieldInterface::class);
        $field->method('getType')->willReturn($fieldType);

        $this->expectException(DataSourceViewException::class);
        new DataSourceView('datasource', [$field, $field], [], []);
    }

    public function testInterfacesImplementation(): void
    {
        $fields = [];
        for ($x = 0; $x < 5; $x++) {
            $fieldView = $this->createMock(FieldViewInterface::class);
            $fieldView->method('getName')->willReturn("name{$x}");
            $fieldType = $this->createMock(FieldTypeInterface::class);
            $fieldType->method('createView')->willReturn($fieldView);
            $field = $this->createMock(FieldInterface::class);
            $field->method('getType')->willReturn($fieldType);

            $fields[] = $field;
        }

        $view = new DataSourceView('datasource', [$fields[0]], [], []);

        self::assertCount(1, $view);
        self::assertTrue(isset($view['name0']));
        self::assertFalse(isset($view['name1']));

        foreach ($view as $key => $value) {
            self::assertEquals('name0', $key);
        }

        $view = new DataSourceView('datasource', [$fields[0], $fields[1], $fields[2]], [], []);

        self::assertEquals('name0', $view->key());
        $view->next();
        self::assertEquals('name1', $view->key());

        $view = new DataSourceView('datasource', [$fields[0], $fields[1], $fields[2], $fields[3], $fields[4]], [], []);

        // After adding fields iterator resets on its own.
        self::assertEquals('name0', $view->key());

        self::assertCount(5, $view);
        self::assertTrue(isset($view['name1']));

        $view->seek(1);
        self::assertEquals('name1', $view->current()->getName());
        self::assertEquals('name1', $view->key());

        $fields = [];
        for ($view->rewind(); $view->valid(); $view->next()) {
            $fields[] = $view->current()->getName();
        }

        $expected = ['name0', 'name1', 'name2', 'name3', 'name4'];
        self::assertEquals($expected, $fields);

        self::assertEquals('name3', $view['name3']->getName());

        $this->expectException(DataSourceViewException::class);
        $view['name0'] = 'trash';
        unset($view['name0']);
    }
}
