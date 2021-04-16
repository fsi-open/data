<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Collection\CollectionAbstractField;

class Text extends CollectionAbstractField
{
    protected $comparisons = ['eq', 'neq', 'in', 'notIn', 'contains'];

    public function getType(): string
    {
        return 'text';
    }
}
