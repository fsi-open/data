<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Doctrine;

use FSi\Component\DataGrid\Extension\Doctrine\ColumnType\Entity;
use FSi\Component\DataGrid\Extension\Doctrine\DoctrineExtension;
use PHPUnit\Framework\TestCase;

class DoctrineExtensionTest extends TestCase
{
    public function testLoadedTypes(): void
    {
        $extension = new DoctrineExtension();

        self::assertTrue($extension->hasColumnType('entity'));
        self::assertTrue($extension->hasColumnType(Entity::class));
        self::assertFalse($extension->hasColumnType('foo'));
    }
}
