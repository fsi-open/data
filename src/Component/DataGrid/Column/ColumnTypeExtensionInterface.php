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
     * @return array<int,class-string<ColumnTypeInterface>>
     */
    public static function getExtendedColumnTypes(): array;

    /**
     * @param ColumnInterface $column
     * @param CellViewInterface $view
     * @param int|string $index
     * @param array<string,mixed>|object $source
     */
    public function buildCellView(ColumnInterface $column, CellViewInterface $view, $index, $source): void;

    public function buildHeaderView(ColumnInterface $column, HeaderViewInterface $view): void;

    /**
     * @param ColumnInterface $column
     * @param mixed $value
     * @return mixed
     */
    public function filterValue(ColumnInterface $column, $value);

    public function initOptions(OptionsResolver $optionsResolver): void;
}
