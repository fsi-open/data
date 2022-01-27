<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\Twig\Extension;

use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use FSi\Component\DataGrid\DataGridViewInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\TemplateWrapper;

use function array_key_exists;
use function array_merge;
use function count;
use function implode;
use function sprintf;

class DataGridRuntime implements RuntimeExtensionInterface
{
    private Environment $environment;
    private TranslatorInterface $translator;
    /**
     * @var array<string>
     */
    private array $baseThemesNames;
    /**
     * @var array<TemplateWrapper>
     */
    private array $baseThemes;
    /**
     * @var array<string,TemplateWrapper>
     */
    private array $themes;
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $themesVars;

    /**
     * @param TranslatorInterface $translator
     * @param Environment $environment
     * @param array<string> $themes
     */
    public function __construct(TranslatorInterface $translator, Environment $environment, array $themes)
    {
        $this->baseThemesNames = $themes;
        $this->translator = $translator;
        $this->environment = $environment;
        $this->themes = [];
        $this->themesVars = [];
        $this->baseThemes = [];
    }

    /**
     * @param DataGridViewInterface $dataGrid
     * @param TemplateWrapper|string $theme
     * @param array<string,mixed> $vars
     */
    public function setTheme(DataGridViewInterface $dataGrid, $theme, array $vars = []): void
    {
        if (false === $theme instanceof TemplateWrapper) {
            $theme = $this->environment->load($theme);
        }

        $dataGridName = $dataGrid->getName();
        $this->themes[$dataGridName] = $theme;
        $this->themesVars[$dataGridName] = $vars;
    }

    public function dataGrid(DataGridViewInterface $view): string
    {
        $blockNames = [
            "datagrid_{$view->getName()}",
            'datagrid',
        ];

        $context = [
            'datagrid' => $view,
            'vars' => $this->getDataGridVars($view->getName())
        ];

        return $this->renderTheme($view->getName(), $context, $blockNames);
    }

    /**
     * @param DataGridViewInterface $view
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataGridHeader(DataGridViewInterface $view, array $vars = []): string
    {
        $blockNames = [
            "datagrid_{$view->getName()}_header",
            'datagrid_header',
        ];

        $context = [
            'headers' => $view->getHeaders(),
            'vars' => array_merge(
                $this->getDataGridVars($view->getName()),
                $vars
            )
        ];

        return $this->renderTheme($view->getName(), $context, $blockNames);
    }

    /**
     * @param HeaderViewInterface $view
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataGridColumnHeader(HeaderViewInterface $view, array $vars = []): string
    {
        $dataGridName = $view->getDataGridName();
        $blockNames = [
            "datagrid_{$dataGridName}_column_name_{$view->getName()}_header",
            "datagrid_{$dataGridName}_column_type_{$view->getType()}_header",
            "datagrid_column_name_{$view->getName()}_header",
            "datagrid_column_type_{$view->getType()}_header",
            "datagrid_{$dataGridName}_column_header",
            'datagrid_column_header',
        ];

        $context = [
            'header' => $view,
            'translation_domain' => $view->getAttribute('translation_domain'),
            'vars' => array_merge($this->getDataGridVars($dataGridName), $vars)
        ];

        return $this->renderTheme($dataGridName, $context, $blockNames);
    }

    /**
     * @param DataGridViewInterface $view
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataGridRowset(DataGridViewInterface $view, array $vars = []): string
    {
        $blockNames = [
            "datagrid_{$view->getName()}_rowset",
            'datagrid_rowset',
        ];

        $context = [
            'datagrid' => $view,
            'vars' => array_merge($this->getDataGridVars($view->getName()), $vars)
        ];

        return $this->renderTheme($view->getName(), $context, $blockNames);
    }

    /**
     * @param CellViewInterface $view
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataGridColumnCell(CellViewInterface $view, array $vars = []): string
    {
        $dataGridName = $view->getDataGridName();
        $blockNames = [
            "datagrid_{$dataGridName}_column_name_{$view->getName()}_cell",
            "datagrid_{$dataGridName}_column_type_{$view->getType()}_cell",
            "datagrid_column_name_{$view->getName()}_cell",
            "datagrid_column_type_{$view->getType()}_cell",
            "datagrid_{$dataGridName}_column_cell",
            'datagrid_column_cell',
        ];

        $context = [
            'cell' => $view,
            'row_index' => $view->getAttribute('index'),
            'datagrid_name' => $dataGridName,
            'translation_domain' => $view->getAttribute('translation_domain'),
            'vars' => array_merge($this->getDataGridVars($dataGridName), $vars)
        ];

        return $this->renderTheme($dataGridName, $context, $blockNames);
    }

    /**
     * @param CellViewInterface $view
     * @param array<string,mixed> $vars
     * @return string
     */
    public function dataGridColumnCellForm(CellViewInterface $view, array $vars = []): string
    {
        if (false === $view->hasAttribute('form')) {
            return '';
        }

        $dataGridName = $view->getDataGridName();
        $blockNames = [
            "datagrid_{$dataGridName}_column_name_{$view->getName()}_cell_form",
            "datagrid_{$dataGridName}_column_type_{$view->getType()}_cell_form",
            "datagrid_column_name_{$view->getName()}_cell_form",
            "datagrid_column_type_{$view->getType()}_cell_form",
            "datagrid_{$dataGridName}_column_cell_form",
            'datagrid_column_cell_form',
        ];

        $context = [
            'form' => $view->getAttribute('form'),
            'vars' => array_merge($this->getDataGridVars($dataGridName), $vars)
        ];

        return $this->renderTheme($dataGridName, $context, $blockNames);
    }

    /**
     * @param CellViewInterface $view
     * @param string $action
     * @param string $content
     * @param array<string,string> $urlAttrs
     * @param array<string,string> $fieldMappingValues
     * @return string
     */
    public function dataGridColumnActionCellActionWidget(
        CellViewInterface $view,
        string $action,
        string $content,
        array $urlAttrs = [],
        array $fieldMappingValues = []
    ): string {
        $dataGridName = $view->getDataGridName();
        $blockNames = [
            "datagrid_{$dataGridName}_column_type_action_cell_action_{$action}",
            "datagrid_column_type_action_cell_action_{$action}",
            "datagrid_{$dataGridName}_column_type_action_cell_action",
            'datagrid_column_type_action_cell_action',
        ];

        $context = [
            'cell' => $view,
            'action' => $action,
            'content' => $content,
            'attr' => $urlAttrs,
            'translation_domain' => $view->getAttribute('translation_domain'),
            'field_mapping_values' => $fieldMappingValues
        ];

        return $this->renderTheme($dataGridName, $context, $blockNames);
    }

    /**
     * @param array<string,string> $attributes
     * @param string|null $translationDomain
     * @return string
     */
    public function dataGridAttributes(array $attributes, ?string $translationDomain = null): string
    {
        $attrs = [];

        foreach ($attributes as $attributeName => $attributeValue) {
            if ($attributeName === 'title') {
                $attributeValue = $this->translator->trans($attributeValue, [], $translationDomain);
            }

            $attrs[] = sprintf('%s="%s"', $attributeName, $attributeValue);
        }

        return ' ' . implode(' ', $attrs);
    }

    /**
     * @param string $dataGridName
     * @return array<TemplateWrapper>
     */
    private function getTemplates(string $dataGridName): array
    {
        $this->initThemes();

        $templates = [];

        if (true === array_key_exists($dataGridName, $this->themes)) {
            $templates[] = $this->themes[$dataGridName];
        }

        for ($i = count($this->baseThemes) - 1; $i >= 0; $i--) {
            $templates[] = $this->baseThemes[$i];
        }

        return $templates;
    }

    private function initThemes(): void
    {
        if (count($this->baseThemes) === count($this->baseThemesNames)) {
            return;
        }

        for ($i = count($this->baseThemesNames) - 1; $i >= 0; $i--) {
            $this->baseThemes[$i] = $this->environment->load($this->baseThemesNames[$i]);
        }
    }

    /**
     * @param string $dataGridName
     * @return array<string,mixed>
     */
    private function getDataGridVars(string $dataGridName): array
    {
        return $this->themesVars[$dataGridName] ?? [];
    }

    /**
     * @param string $dataGridName
     * @param array<string,mixed> $contextVars
     * @param array<string> $availableBlocks
     * @return string
     */
    private function renderTheme(
        string $dataGridName,
        array $contextVars = [],
        array $availableBlocks = []
    ): string {
        $templates = $this->getTemplates($dataGridName);
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
