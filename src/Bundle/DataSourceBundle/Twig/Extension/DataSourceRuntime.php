<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\Extension;

use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Field\FieldViewInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\TemplateWrapper;

use function array_key_exists;
use function spl_object_id;

class DataSourceRuntime implements RuntimeExtensionInterface
{
    /**
     * Default theme key in themes array.
     */
    public const DEFAULT_THEME = '_default_theme';

    /**
     * @var array<string,TemplateWrapper>
     */
    private array $themes;
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $themesVars;
    /**
     * @var array<string,string>
     */
    private array $routes;
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $additionalParameters;
    private RequestStack $requestStack;
    private Environment $environment;
    private RouterInterface $router;
    private string $baseTemplate;

    public function __construct(
        RequestStack $requestStack,
        Environment $environment,
        RouterInterface $router,
        string $template
    ) {
        $this->themes = [];
        $this->themesVars = [];
        $this->routes = [];
        $this->additionalParameters = [];
        $this->requestStack = $requestStack;
        $this->environment = $environment;
        $this->router = $router;
        $this->baseTemplate = $template;
    }

    /**
     * Set theme for specific DataSource.
     * Theme is nothing more than twig template that contains some or all of blocks required to render DataSource.
     *
     * @param DataSourceViewInterface<FieldViewInterface> $dataSource
     * @param TemplateWrapper|string $theme
     * @param array<string,mixed> $vars
     */
    public function setTheme(DataSourceViewInterface $dataSource, $theme, array $vars = []): void
    {
        if (false === $theme instanceof TemplateWrapper) {
            $theme = $this->environment->load($theme);
        }

        $this->themes[$dataSource->getName()] = $theme;
        $this->themesVars[$dataSource->getName()] = $vars;
    }

    /**
     * Set route and optionally additional parameters for specific DataSource.
     *
     * @param DataSourceViewInterface<FieldViewInterface> $dataSource
     * @param string $route
     * @param array<string,mixed> $additionalParameters
     */
    public function setRoute(DataSourceViewInterface $dataSource, string $route, array $additionalParameters = []): void
    {
        $this->routes[$dataSource->getName()] = $route;
        $this->additionalParameters[$dataSource->getName()] = $additionalParameters;
    }

    /**
     * @param DataSourceViewInterface<FieldViewInterface> $view
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataSourceFilter(DataSourceViewInterface $view, array $vars = []): string
    {
        $blockNames = [
            'datasource_' . $view->getName() . '_filter',
            'datasource_filter',
        ];

        $viewData = [
            'datasource' => $view,
            'vars' => array_merge(
                $this->getVars($view->getName()),
                $vars
            )
        ];

        return $this->renderTheme($view->getName(), $viewData, $blockNames);
    }

    /**
     * @param DataSourceViewInterface<FieldViewInterface> $view
     * @return int
     */
    public function dataSourceFilterCount(DataSourceViewInterface $view): int
    {
        $count = 0;
        foreach ($view as $field) {
            if (true === $field->hasAttribute('form')) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param FieldViewInterface $fieldView
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataSourceField(FieldViewInterface $fieldView, array $vars = []): string
    {
        $dataSourceName = $fieldView->getDataSourceName();
        $blockNames = [
            "datasource_{$dataSourceName}_field_name_{$fieldView->getName()}",
            "datasource_{$dataSourceName}_field_type_{$fieldView->getType()}",
            "datasource_field_name_{$fieldView->getName()}",
            "datasource_field_type_{$fieldView->getType()}",
            "datasource_{$dataSourceName}_field",
            'datasource_field',
        ];

        $viewData = [
            'field' => $fieldView,
            'vars' => array_merge(
                $this->getVars($dataSourceName),
                $vars
            )
        ];

        return $this->renderTheme($dataSourceName, $viewData, $blockNames);
    }

    /**
     * @param FieldViewInterface $fieldView
     * @param array<string,mixed> $options
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataSourceSort(FieldViewInterface $fieldView, array $options = [], array $vars = []): string
    {
        if (false === $fieldView->getAttribute('sortable')) {
            return '';
        }

        $dataSourceName = $fieldView->getDataSourceName();
        $blockNames = [
            "datasource_{$dataSourceName}_sort",
            'datasource_sort',
        ];

        $options = $this->resolveSortOptions($options, $dataSourceName);
        $ascendingUrl = $this->getUrl(
            $dataSourceName,
            $options,
            $fieldView->getAttribute('parameters_sort_ascending')
        );
        $descendingUrl = $this->getUrl(
            $dataSourceName,
            $options,
            $fieldView->getAttribute('parameters_sort_descending')
        );

        $viewData = [
            'field' => $fieldView,
            'ascending_url' => $ascendingUrl,
            'descending_url' => $descendingUrl,
            'ascending' => $options['ascending'],
            'descending' => $options['descending'],
            'vars' => array_merge(
                $this->getVars($dataSourceName),
                $vars
            )
        ];

        return $this->renderTheme($dataSourceName, $viewData, $blockNames);
    }

    /**
     * @param DataSourceViewInterface<FieldViewInterface> $view
     * @param array<string,mixed> $options
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataSourcePagination(DataSourceViewInterface $view, array $options = [], array $vars = []): string
    {
        $blockNames = [
            'datasource_' . $view->getName() . '_pagination',
            'datasource_pagination',
        ];

        $options = $this->resolvePaginationOptions($options, $view);

        $pagesParams = $view->getAttribute('parameters_pages');
        $current = (int) $view->getAttribute('page');
        $pageCount = count($pagesParams);
        if ($pageCount < 2) {
            return '';
        }

        if (true === array_key_exists('max_pages', $options)) {
            $delta = ceil($options['max_pages'] / 2);

            if ($current - $delta > $pageCount - $options['max_pages']) {
                $pages = range(max($pageCount - $options['max_pages'] + 1, 1), $pageCount);
            } else {
                if ($current - $delta < 0) {
                    $delta = $current;
                }

                $offset = $current - $delta;
                $pages = range($offset + 1, min($offset + $options['max_pages'], $pageCount));
            }
        } else {
            $pages = range(1, $pageCount);
        }
        $pagesAnchors = [];
        $pagesUrls = [];
        foreach ($pages as $page) {
            $pagesUrls[$page] = $this->getUrl($view->getName(), $options, $pagesParams[$page]);
        }

        $viewData = [
            'datasource' => $view,
            'page_anchors' => $pagesAnchors,
            'pages_urls' => $pagesUrls,
            'first' => 1,
            'first_url' => $this->getUrl($view->getName(), $options, $pagesParams[1]),
            'last' => $pageCount,
            'last_url' => $this->getUrl($view->getName(), $options, $pagesParams[$pageCount]),
            'current' => $current,
            'active_class' => $options['active_class'],
            'disabled_class' => $options['disabled_class'],
            'translation_domain' => $options['translation_domain'],
            'vars' => array_merge($this->getVars($view->getName()), $vars),
        ];
        if (1 !== $current && true === array_key_exists($current - 1, $pagesParams)) {
            $viewData['prev'] = $current - 1;
            $viewData['prev_url'] = $this->getUrl($view->getName(), $options, $pagesParams[$current - 1]);
        }
        if ($pageCount !== $current && true === array_key_exists($current + 1, $pagesParams)) {
            $viewData['next'] = $current + 1;
            $viewData['next_url'] = $this->getUrl($view->getName(), $options, $pagesParams[$current + 1]);
        }

        return $this->renderTheme($view->getName(), $viewData, $blockNames);
    }

    /**
     * @param DataSourceViewInterface<FieldViewInterface> $view
     * @param array<string,mixed> $options
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataSourceMaxResults(DataSourceViewInterface $view, array $options = [], array $vars = []): string
    {
        $options = $this->resolveMaxResultsOptions($options, $view);
        $blockNames = [
            'datasource_' . $view->getName() . '_max_results',
            'datasource_max_results',
        ];

        $baseParameters = $view->getAllParameters();
        if (false === array_key_exists($view->getName(), $baseParameters)) {
            $baseParameters[$view->getName()] = [];
        }

        $results = [];
        foreach ($options['results'] as $resultsPerPage) {
            $baseParameters[$view->getName()][PaginationExtension::PARAMETER_MAX_RESULTS] = $resultsPerPage;
            $results[$resultsPerPage] = $this->getUrl($view->getName(), $options, $baseParameters);
        }

        $viewData = [
            'datasource' => $view,
            'results' => $results,
            'active_class' => $options['active_class'],
            'max_results' => $view->getAttribute('max_results'),
            'vars' => array_merge($this->getVars($view->getName()), $vars),
        ];

        return $this->renderTheme($view->getName(), $viewData, $blockNames);
    }

    /**
     * @param array<string,mixed> $options
     * @param DataSourceViewInterface<FieldViewInterface> $dataSource
     * @return array<string,mixed>
     */
    private function resolvePaginationOptions(array $options, DataSourceViewInterface $dataSource): array
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefined(['max_pages'])
            ->setDefaults([
                'route' => $this->getCurrentRoute($dataSource->getName()),
                'additional_parameters' => [],
                'active_class' => 'active',
                'disabled_class' => 'disabled',
                'translation_domain' => 'DataSourceBundle'
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('additional_parameters', 'array')
            ->setAllowedTypes('max_pages', 'int')
            ->setAllowedTypes('active_class', 'string')
            ->setAllowedTypes('disabled_class', 'string')
            ->setAllowedTypes('translation_domain', 'string');

        return $optionsResolver->resolve($options);
    }

    /**
     * Validate and resolve options passed in Twig to datasource_results_per_page_widget
     *
     * @param array<string,mixed> $options
     * @param DataSourceViewInterface<FieldViewInterface> $dataSource
     * @return array<string,mixed>
     */
    private function resolveMaxResultsOptions(array $options, DataSourceViewInterface $dataSource): array
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefaults([
                'route' => $this->getCurrentRoute($dataSource->getName()),
                'active_class' => 'active',
                'additional_parameters' => [],
                'results' => [5, 10, 20, 50, 100]
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('active_class', 'string')
            ->setAllowedTypes('additional_parameters', 'array')
            ->setAllowedTypes('results', 'array');

        return $optionsResolver->resolve($options);
    }

    /**
     * @param array<string,mixed> $options
     * @param string $dataSourceName
     * @return array<string,mixed>
     */
    private function resolveSortOptions(array $options, string $dataSourceName): array
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefaults([
                'route' => $this->getCurrentRoute($dataSourceName),
                'additional_parameters' => [],
                'ascending' => '&uarr;',
                'descending' => '&darr;',
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('additional_parameters', 'array')
            ->setAllowedTypes('ascending', 'string')
            ->setAllowedTypes('descending', 'string');

        return $optionsResolver->resolve($options);
    }

    private function initTemplate(): void
    {
        if (true === array_key_exists(self::DEFAULT_THEME, $this->themes)) {
            return;
        }

        $this->themes[self::DEFAULT_THEME] = $this->environment->load($this->baseTemplate);
    }

    private function getCurrentRoute(string $dataSourceName): string
    {
        if (true === array_key_exists($dataSourceName, $this->routes)) {
            return $this->routes[$dataSourceName];
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new RuntimeException('Some datasource widget was called out of any request scope.');
        }

        if ($request->attributes->get('_route') === '_fragment') {
            throw new RuntimeException(
                'Some datasource widget was called during Symfony internal request.
                You must use {% datasource_route %} twig tag to specify target
                route and/or additional parameters for this datasource\'s actions'
            );
        }

        $parameters = $this->router->match($request->getPathInfo());
        return $parameters['_route'];
    }

    /**
     * Return list of templates that might be useful to render DataSourceView.
     * Always the last template will be default one.
     *
     * @param string $dataSourceName
     * @return array<TemplateWrapper>
     */
    private function getTemplates(string $dataSourceName): array
    {
        $this->initTemplate();

        $templates = [];
        if (array_key_exists($dataSourceName, $this->themes)) {
            $templates[] = $this->themes[$dataSourceName];
        }
        $templates[] = $this->themes[self::DEFAULT_THEME];

        return $templates;
    }

    /**
     * Return vars passed to theme. Those vars will be added to block context.
     *
     * @param string $dataSourceName
     * @return array<string,mixed>
     */
    private function getVars(string $dataSourceName): array
    {
        return $this->themesVars[$dataSourceName] ?? [];
    }

    /**
     * Return additional parameters that should be passed to the URL generation for specified datasource.
     *
     * @param string $dataSourceName
     * @param array<string,mixed> $options
     * @param array<string,mixed> $parameters
     * @return string
     */
    private function getUrl(string $dataSourceName, array $options = [], array $parameters = []): string
    {
        return $this->router->generate(
            $options['route'],
            array_merge(
                $this->additionalParameters[$dataSourceName] ?? [],
                $options['additional_parameters'] ?? [],
                $parameters
            )
        );
    }

    /**
     * @param string $dataSourceName
     * @param array<string,mixed> $contextVars
     * @param array<int,string> $availableBlocks
     * @return string
     */
    private function renderTheme(
        string $dataSourceName,
        array $contextVars = [],
        array $availableBlocks = []
    ): string {
        $templates = $this->getTemplates($dataSourceName);
        $contextVars = $this->environment->mergeGlobals($contextVars);

        foreach ($availableBlocks as $blockName) {
            foreach ($templates as $template) {
                if (true === $template->hasBlock($blockName, $contextVars)) {
                    return $template->renderBlock($blockName, $contextVars);
                }
            }
        }

        return '';
    }
}
