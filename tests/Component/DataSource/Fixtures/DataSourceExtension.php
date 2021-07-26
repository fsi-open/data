<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\DataSourceAbstractExtension;
use FSi\Component\DataSource\Event\DataSourceEvents;

/**
 * Class to test DataSource extensions calls.
 */
class DataSourceExtension extends DataSourceAbstractExtension implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $calls = [];

    public static function getSubscribedEvents(): array
    {
        return [
            DataSourceEvents::PRE_BIND_PARAMETERS => ['preBindParameters', 128],
            DataSourceEvents::POST_BIND_PARAMETERS => ['postBindParameters', 128],
            DataSourceEvents::PRE_GET_RESULT => ['preGetResult', 128],
            DataSourceEvents::POST_GET_RESULT => ['postGetResult', 128],
            DataSourceEvents::PRE_BUILD_VIEW => ['preBuildView', 128],
            DataSourceEvents::POST_BUILD_VIEW => ['postBuildView', 128],
            DataSourceEvents::PRE_GET_PARAMETERS => ['preGetParameters', 128],
            DataSourceEvents::POST_GET_PARAMETERS => ['postGetParameters', 128],
        ];
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function resetCalls(): void
    {
        $this->calls = [];
    }

    public function __call(string $name, array $arguments): void
    {
        $this->calls[] = $name;
    }

    public function loadSubscribers(): array
    {
        return [$this];
    }
}
