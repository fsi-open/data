<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\DataSource\Extension;

use FSi\Bundle\DataSourceBundle\DataSource\EventSubscriber\BindParameters;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\PreBindParameters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CoreExtensionTest extends TestCase
{
    public function testBindParameters(): void
    {
        $datasource = $this->createMock(DataSourceInterface::class);
        $data1 = ['key1' => 'value1', 'key2' => 'value2'];
        $data2 = $data1;

        $subscriber = new BindParameters();

        $args = new PreBindParameters($datasource, $data2);
        ($subscriber)($args);
        $data2 = $args->getParameters();
        self::assertEquals($data1, $data2);

        $request = new Request($data2);
        $args = new PreBindParameters($datasource, $request);
        ($subscriber)($args);
        $request = $args->getParameters();
        self::assertIsArray($request);
        self::assertEquals($data1, $request);
    }
}
