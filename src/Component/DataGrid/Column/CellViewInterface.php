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

interface CellViewInterface
{
    public function hasAttribute(string $name): bool;

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

    /**
     * @return array<string,mixed>
     */
    public function getAttributes(): array;

    /**
     * @param array|object $source
     */
    public function setSource($source): void;

    /**
     * @return array|object
     */
    public function getSource();

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param mixed $value
     */
    public function setValue($value): void;

    public function getType(): string;

    public function getName(): string;

    public function setDataGridView(DataGridViewInterface $dataGrid): void;

    public function getDataGridView(): DataGridViewInterface;
}
