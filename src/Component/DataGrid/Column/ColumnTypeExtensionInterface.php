<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ColumnTypeExtensionInterface
{
    /**
     * @param ColumnInterface $column
     * @param int|string $index
     * @param array<string,mixed>|object $object
     * @param mixed $data
     */
    public function bindData(ColumnInterface $column, $index, $object, $data): void;

    public function buildCellView(ColumnInterface $column, CellViewInterface $view): void;

    public function buildHeaderView(ColumnInterface $column, HeaderViewInterface $view): void;

    /**
     * @param ColumnInterface $column
     * @param mixed $value
     * @return mixed
     */
    public function filterValue(ColumnInterface $column, $value);

    public function initOptions(OptionsResolver $optionsResolver): void;

    /**
     * @return array<int,class-string<ColumnTypeInterface>>
     */
    public function getExtendedColumnTypes(): array;
}
