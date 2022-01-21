<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceInterface;

interface FieldInterface
{
    public function getType(): FieldTypeInterface;
    public function getName(): string;
    public function getDataSource(): DataSourceInterface;
    /**
     * @param string $name
     * @return mixed
     */
    public function getOption(string $name);
    public function hasOption(string $name): bool;
    /**
     * @param mixed $parameter
     */
    public function bindParameter($parameter): void;
    /**
     * @return mixed
     */
    public function getParameter();
    public function isDirty(): bool;
    public function setDirty(bool $dirty = true): void;
}
