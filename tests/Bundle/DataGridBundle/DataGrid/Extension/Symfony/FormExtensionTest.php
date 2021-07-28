<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony;

use FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\FormExtension;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use FSi\Component\DataGrid\Extension\Core\ColumnType\DateTime;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Number;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Text;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Entity;
use FSi\Component\DataGrid\Extension\Gedmo\ColumnType\Tree;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class FormExtensionTest extends TestCase
{
    public function testSymfonyFormExtension(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $extension = new FormExtension($formFactory);

        self::assertFalse($extension->hasColumnType('foo'));
        self::assertTrue($extension->hasColumnTypeExtensions($this->createMock(Text::class)));
        self::assertTrue($extension->hasColumnTypeExtensions($this->createMock(Boolean::class)));
        self::assertTrue($extension->hasColumnTypeExtensions($this->createMock(Number::class)));
        self::assertTrue($extension->hasColumnTypeExtensions($this->createMock(DateTime::class)));
        self::assertTrue($extension->hasColumnTypeExtensions($this->createMock(Entity::class)));
        self::assertTrue($extension->hasColumnTypeExtensions($this->createMock(Tree::class)));
    }
}
