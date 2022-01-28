<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldViewTest extends TestCase
{
    public function testFieldViewCreation(): void
    {
        /** @var FieldTypeInterface&MockObject $fieldType */
        $fieldType = $this->createMock(FieldTypeInterface::class);
        $fieldType->expects(self::atLeastOnce())->method('getId')->willReturn('sometype');

        /** @var FieldInterface&MockObject $field */
        $field = $this->createMock(FieldInterface::class);
        $field->expects(self::atLeastOnce())->method('getName')->willReturn('somename');
        $field->expects(self::atLeastOnce())->method('getType')->willReturn($fieldType);
        $field->expects(self::atLeastOnce())->method('getParameter')->willReturn('someparam');

        $fieldView = new FieldView($field);

        self::assertEquals($field->getName(), $fieldView->getName());
        self::assertEquals($field->getType()->getId(), $fieldView->getType());
        self::assertEquals($field->getParameter(), $fieldView->getParameter());
    }

    public function testCorrectReferenceToDataSourceView(): void
    {
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('datasource');
        $field = $this->createMock(FieldInterface::class);
        $field->method('getDataSource')->willReturn($dataSource);
        $fieldView = new FieldView($field);

        self::assertEquals($fieldView->getDataSourceName(), 'datasource');
    }

    public function testFieldViewAttributesMethods(): void
    {
        $field = $this->createMock(FieldInterface::class);
        $view = new FieldView($field);

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

        self::assertNull($view->getAttribute('option5'));
    }
}
