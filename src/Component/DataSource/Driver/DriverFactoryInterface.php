<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver;

interface DriverFactoryInterface
{
    public static function getDriverType(): string;
    /**
     * @param array<string,mixed> $options
     * @return DriverInterface
     */
    public function createDriver(array $options = []): DriverInterface;
}
