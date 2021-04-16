<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field;

use Doctrine\DBAL\Types\Types;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField;

class Date extends DBALAbstractField
{
    protected $comparisons = ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'notIn', 'between', 'isNull'];

    public function getType(): string
    {
        return 'date';
    }

    public function getDBALType(): ?string
    {
        return Types::DATE_IMMUTABLE;
    }
}
