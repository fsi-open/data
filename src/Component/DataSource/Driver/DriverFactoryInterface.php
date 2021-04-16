<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver;

/**
 * Factory for creating drivers.
 */
interface DriverFactoryInterface
{
    /**
     * Return driver type name.
     * For example if you are using Doctrine\DriverFactory this method will return 'doctrine' string.
     */
    public function getDriverType(): string;

    public function createDriver(array $options = []): DriverInterface;
}
