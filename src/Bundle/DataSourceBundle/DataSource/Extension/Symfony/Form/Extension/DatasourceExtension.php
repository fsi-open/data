<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Extension;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Type\BetweenType;
use Symfony\Component\Form\AbstractExtension;

final class DatasourceExtension extends AbstractExtension
{
    protected function loadTypes(): array
    {
        return [new BetweenType()];
    }
}
