<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\EventSubscriber;

use FSi\Bundle\DataSourceBundle\DataSource\FormStorage;
use FSi\Component\DataSource\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Field\Event\PreGetParameter;

final class FieldPreGetParameter implements DataSourceEventSubscriberInterface
{
    private FormStorage $formStorage;

    public function __construct(FormStorage $formStorage)
    {
        $this->formStorage = $formStorage;
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public function __invoke(PreGetParameter $event): void
    {
        $event->setParameter($this->formStorage->getParameter($event->getField()));
    }
}
