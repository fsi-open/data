<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\Twig\Extension;

use FSi\Bundle\DataGridBundle\Twig\TokenParser\DataGridThemeTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DataGridExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('datagrid_widget', [DataGridRuntime::class, 'dataGrid'], ['is_safe' => ['html']]),
            new TwigFunction(
                'datagrid_header_widget',
                [DataGridRuntime::class, 'dataGridHeader'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datagrid_rowset_widget',
                [DataGridRuntime::class, 'dataGridRowset'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datagrid_column_header_widget',
                [DataGridRuntime::class, 'dataGridColumnHeader'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datagrid_column_cell_widget',
                [DataGridRuntime::class, 'dataGridColumnCell'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datagrid_column_cell_form_widget',
                [DataGridRuntime::class, 'dataGridColumnCellForm'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datagrid_column_type_action_cell_action_widget',
                [DataGridRuntime::class, 'dataGridColumnActionCellActionWidget'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datagrid_attributes_widget',
                [DataGridRuntime::class, 'dataGridAttributes'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            new DataGridThemeTokenParser(),
        ];
    }
}
