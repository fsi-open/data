<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver;

use DateTimeImmutable;
use Doctrine\Persistence\ConnectionRegistry;
use Doctrine\Persistence\ManagerRegistry;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFactory;
use FSi\Component\DataSource\Driver\Doctrine\ORM;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Basic tests for Doctrine driver.
 */
class DriverFactoryManagerTest extends TestCase
{
    public function testBasicManagerOperations(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $doctrineDbalFactory = new DBALFactory($this->createMock(ConnectionRegistry::class), $eventDispatcher, []);
        $doctrineOrmFactory = new ORM\DoctrineFactory($this->createMock(ManagerRegistry::class), $eventDispatcher, []);
        $collectionFactory = new CollectionFactory($eventDispatcher, []);

        $manager = new DriverFactoryManager([$doctrineDbalFactory, $doctrineOrmFactory, $collectionFactory]);

        self::assertSame($doctrineDbalFactory, $manager->getFactory('doctrine-dbal'));
        self::assertSame($doctrineOrmFactory, $manager->getFactory('doctrine-orm'));
        self::assertSame($collectionFactory, $manager->getFactory('collection'));
    }
}
