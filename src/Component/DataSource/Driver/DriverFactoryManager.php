<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver;

use FSi\Component\DataSource\Exception\DataSourceException;
use InvalidArgumentException;

use function array_key_exists;

class DriverFactoryManager implements DriverFactoryManagerInterface
{
    /**
     * @var array
     */
    private $factories;

    /**
     * @param array<DriverFactoryInterface> $factories
     * @throws InvalidArgumentException
     */
    public function __construct($factories = [])
    {
        $this->factories = [];

        foreach ($factories as $factory) {
            if (false === $factory instanceof DriverFactoryInterface) {
                throw new InvalidArgumentException(
                    sprintf("Factory must implement %s", DriverFactoryInterface::class)
                );
            }

            $this->factories[$factory->getDriverType()] = $factory;
        }
    }

    public function getFactory(string $driverType): DriverFactoryInterface
    {
        if (false === array_key_exists($driverType, $this->factories)) {
            throw new DataSourceException("Driver \"{$driverType}\" doesn't exist.");
        }

        return $this->factories[$driverType];
    }
}
