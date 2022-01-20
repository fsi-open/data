<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Extension\Core;

use FSi\Component\DataSource\Field\FieldAbstractType;
use FSi\Component\DataSource\Field\Type\NumberTypeInterface;

final class FakeFieldType extends FieldAbstractType implements NumberTypeInterface
{
    public function getId(): string
    {
        return 'fake';
    }
}
