<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\Fixtures;

use Twig\Extension\RuntimeExtensionInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

use function get_class;

class TwigRuntimeLoader implements RuntimeLoaderInterface
{
    private $instances = [];

    public function __construct(array $instances)
    {
        foreach ($instances as $instance) {
            $this->instances[get_class($instance)] = $instance;
        }
    }

    public function replaceInstance(RuntimeExtensionInterface $runtime): void
    {
        $this->instances[get_class($runtime)] = $runtime;
    }

    public function load($class)
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        return null;
    }
}
