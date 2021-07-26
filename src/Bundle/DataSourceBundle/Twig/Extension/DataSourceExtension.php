<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\Extension;

use FSi\Bundle\DataSourceBundle\Twig\TokenParser\DataSourceRouteTokenParser;
use FSi\Bundle\DataSourceBundle\Twig\TokenParser\DataSourceThemeTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DataSourceExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'datasource_filter_widget',
                [DataSourceRuntime::class, 'dataSourceFilter'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datasource_filter_count',
                [DataSourceRuntime::class, 'dataSourceFilterCount'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datasource_field_widget',
                [DataSourceRuntime::class, 'dataSourceField'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datasource_sort_widget',
                [DataSourceRuntime::class, 'dataSourceSort'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datasource_pagination_widget',
                [DataSourceRuntime::class, 'dataSourcePagination'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'datasource_max_results_widget',
                [DataSourceRuntime::class, 'dataSourceMaxResults'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            new DataSourceThemeTokenParser(),
            new DataSourceRouteTokenParser(),
        ];
    }
}
