<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Event\DataSourceEvent;

use FSi\Component\DataSource\Field\FieldInterface;

final class PreBuildView
{
    /**
     * @var array<FieldInterface>
     */
    private array $fields;

    /**
     * @param array<FieldInterface> $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array<FieldInterface>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param array<FieldInterface> $fields
     * @return void
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }
}
