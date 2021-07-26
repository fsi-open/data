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

interface HeaderViewInterface
{
    /**
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute(string $name, $value): void;

    /**
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name);

    public function hasAttribute(string $name): bool;

    /**
     * @return array<string,mixed>
     */
    public function getAttributes(): array;

    public function getLabel(): ?string;

    public function setLabel(string $label): void;

    public function getName(): string;

    public function getType(): string;

    public function setDataGridView(DataGridViewInterface $dataGrid): void;

    public function getDataGridView(): DataGridViewInterface;
}
