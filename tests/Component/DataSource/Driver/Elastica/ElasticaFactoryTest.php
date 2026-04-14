<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Elastica;

use Elastica\SearchableInterface;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Driver\Elastica\ElasticaDriver;
use FSi\Component\DataSource\Driver\Elastica\ElasticaFactory;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElasticaFactoryTest extends TestCase
{
    public function testDriverCreation(): void
    {
        $factory = new ElasticaFactory($this->createMock(EventDispatcherInterface::class), []);

        $driver = $factory->createDriver(['searchable' => $this->createMock(SearchableInterface::class)]);
        $this->assertInstanceOf(ElasticaDriver::class, $driver);
    }
}
