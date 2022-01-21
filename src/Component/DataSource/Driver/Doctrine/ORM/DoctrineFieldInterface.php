<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;

interface DoctrineFieldInterface extends FieldTypeInterface
{
    public function buildQuery(QueryBuilder $qb, string $alias, FieldInterface $field): void;
    public function getDBALType(): ?string;
}
