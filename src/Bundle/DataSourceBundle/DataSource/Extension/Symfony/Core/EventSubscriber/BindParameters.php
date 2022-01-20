<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Core\EventSubscriber;

use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;
use Symfony\Component\HttpFoundation\Request;

class BindParameters implements DataSourceEventSubscriberInterface
{
    public static function getPriority(): int
    {
        return 1024;
    }

    public function __invoke(DataSourceEvent\PreBindParameters $event): void
    {
        $parameters = $event->getParameters();
        if (true === $parameters instanceof Request) {
            $event->setParameters($parameters->query->all());
        }
    }
}
