<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Column;

final class CellView implements CellViewInterface
{
    private string $dataGridName;
    private string $name;
    private string $type;
    /**
     * @var mixed
     */
    private $value;
    /**
     * @var array<string,mixed>
     */
    private array $attributes = [];

    /**
     * @param ColumnInterface $column
     * @param mixed $value
     */
    public function __construct(ColumnInterface $column, $value)
    {
        $this->dataGridName = $column->getDataGrid()->getName();
        $this->name = $column->getName();
        $this->type = $column->getType()->getId();
        $this->value = $value;
    }

    public function getDataGridName(): string
    {
        return $this->dataGridName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }
}
