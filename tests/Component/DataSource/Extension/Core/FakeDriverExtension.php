<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Tests\Component\DataSource\Extension\Core;

use FSi\Component\DataSource\Event\DriverEvent;
use FSi\Component\DataSource\Extension\Core\Ordering\Driver\DriverExtension;

final class FakeDriverExtension extends DriverExtension
{
    public function getExtendedDriverTypes(): array
    {
        return [];
    }

    public function sort(array $fields): array
    {
        return $this->sortFields($fields);
    }

    public function preGetResult(DriverEvent\DriverEventArgs $event): void
    {
    }
}
