<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Fixtures\Form\Extension\TestCore;

use Tests\FSi\Bundle\DataSourceBundle\Fixtures\Form\Extension\TestCore\Type;
use Symfony\Component\Form\AbstractExtension;

final class TestCoreExtension extends AbstractExtension
{
    protected function loadTypes(): array
    {
        return [
            new Type\FormType(),
        ];
    }
}
