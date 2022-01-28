<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\Extension;

use FSi\Bundle\DataGridBundle\DataGrid\ColumnType\Action;
use FSi\Bundle\DataGridBundle\DataGrid\Extension\RouterExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

final class RouterExtensionTest extends TestCase
{
    public function testSymfonyExtension(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $extension = new RouterExtension($router, $requestStack);

        self::assertTrue($extension->hasColumnType('action'));
        self::assertTrue($extension->hasColumnType(Action::class));
    }
}
