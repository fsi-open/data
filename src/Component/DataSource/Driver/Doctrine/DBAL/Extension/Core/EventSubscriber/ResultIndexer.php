<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\EventSubscriber;

use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Paginator;
use FSi\Component\DataSource\Event\DriverEvent\ResultEventArgs;
use FSi\Component\DataSource\Event\DriverEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResultIndexer implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [DriverEvents::POST_GET_RESULT => ['postGetResult', 1024]];
    }

    public function postGetResult(ResultEventArgs $event): void
    {
        /** @var DBALDriver $driver */
        $driver = $event->getDriver();
        $indexField = $driver->getIndexField();

        if (null === $indexField) {
            return;
        }

        $result = $event->getResult();

        if (true === $result instanceof Paginator) {
            $event->setResult(new DBALResult($result, $indexField));
        }
    }
}
