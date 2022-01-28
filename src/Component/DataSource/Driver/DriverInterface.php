<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver;

use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\FieldTypeInterface;
use FSi\Component\DataSource\Result;

interface DriverInterface
{
    /**
     * @param array<FieldInterface> $fields
     * @param int|null $first
     * @param int|null $max
     * @return Result
     */
    public function getResult(array $fields, ?int $first, ?int $max): Result;
    public function hasFieldType(string $type): bool;
    public function getFieldType(string $type): FieldTypeInterface;
}
