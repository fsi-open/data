<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Tests\Component\DataSource\Fixtures;

use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Event\FieldEvents;

class FieldExtension extends FieldAbstractExtension
{
    /**
     * @var array
     */
    private $calls = [];

    public static function getSubscribedEvents(): array
    {
        return [
            FieldEvents::PRE_BIND_PARAMETER => ['preBindParameter', 128],
            FieldEvents::POST_BIND_PARAMETER => ['postBindParameter', 128],
            FieldEvents::POST_BUILD_VIEW => ['postBuildView', 128],
            FieldEvents::POST_GET_PARAMETER => ['postGetParameter', 128],
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
}
