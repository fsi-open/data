<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Column;

use FSi\Component\DataGrid\DataGridViewInterface;

final class HeaderView implements HeaderViewInterface
{
    private ?string $label = null;
    private string $name;
    private string $type;
    /**
     * @var array<string,mixed>
     */
    private array $attributes = [];
    /**
     * @var DataGridViewInterface
     */
    private DataGridViewInterface $dataGrid;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setDataGridView(DataGridViewInterface $dataGrid): void
    {
        $this->dataGrid = $dataGrid;
    }

    public function getDataGridView(): DataGridViewInterface
    {
        return $this->dataGrid;
    }
}
