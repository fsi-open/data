<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection\Extension\Core;

use FSi\Component\DataSource\Driver\DriverAbstractExtension;

class CoreExtension extends DriverAbstractExtension
{
    public function getExtendedDriverTypes(): array
    {
        return ['collection'];
    }

    protected function loadFieldTypes(): array
    {
        return [
            new Field\Text(),
            new Field\Number(),
            new Field\Date(),
            new Field\Time(),
            new Field\DateTime(),
            new Field\Boolean(),
        ];
    }
}
