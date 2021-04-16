<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extension of DataSource.
 */
interface DataSourceExtensionInterface
{
    /**
     * Loads events subscribers.
     *
     * @return array<EventSubscriberInterface>
     */
    public function loadSubscribers(): array;

    /**
     * Allows DataSource extension to load extensions directly to its driver.
     *
     * @return array<DriverExtensionInterface>
     */
    public function loadDriverExtensions(): array;
}
