<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Fixtures\Form\Extension\TestCore;

use FSi\Component\DataSource\Field\Type\AbstractFieldType;

final class TestFieldType extends AbstractFieldType
{
    public function getId(): string
    {
        return 'test';
    }
}
