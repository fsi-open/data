<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Column;

final class HeaderView implements HeaderViewInterface
{
    private string $dataGridName;
    private string $name;
    private string $type;
    /**
     * @var array<string,mixed>
     */
    private array $attributes = [];

    public function __construct(ColumnInterface $column)
    {
        $this->dataGridName = $column->getDataGrid()->getName();
        $this->name = $column->getName();
        $this->type = $column->getType()->getId();
    }

    public function getDataGridName(): string
    {
        return $this->dataGridName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }
}
