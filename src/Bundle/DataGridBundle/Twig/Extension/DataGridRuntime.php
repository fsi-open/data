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
use function spl_object_id;

class DataGridRuntime implements RuntimeExtensionInterface
{
    /**
     * @var array<int,TemplateWrapper>
     */
    private array $themes;
    /**
     * @var array<int,array<string,mixed>>
     */
    private array $themesVars;
    /**
     * @var array<string>
     */
    private array $baseThemesNames;
    /**
     * @var array<TemplateWrapper>
     */
    private array $baseThemes;
    private Environment $environment;
    private TranslatorInterface $translator;

    /**
     * @param array<string> $themes
     * @param TranslatorInterface $translator
     * @param Environment $environment
     */
    public function __construct(array $themes, TranslatorInterface $translator, Environment $environment)
    {
        $this->themes = [];
        $this->themesVars = [];
        $this->baseThemesNames = $themes;
        $this->baseThemes = [];
        $this->translator = $translator;
        $this->environment = $environment;
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

        $dataGridId = spl_object_id($dataGrid);
        $this->themes[$dataGridId] = $theme;
        $this->themesVars[$dataGridId] = $vars;
    }

    public function dataGrid(DataGridViewInterface $view): string
    {
        $blockNames = [
            "datagrid_{$view->getName()}",
            'datagrid',
        ];

        $context = [
            'datagrid' => $view,
            'vars' => $this->getDataGridVars($view)
        ];

        return $this->renderTheme($view, $context, $blockNames);
    }

    public function dataGridHeader(DataGridViewInterface $view, array $vars = []): string
    {
        $blockNames = [
            "datagrid_{$view->getName()}_header",
            'datagrid_header',
        ];

        $context = [
            'headers' => $view->getColumns(),
            'vars' => array_merge(
                $this->getDataGridVars($view),
                $vars
            )
        ];

        return $this->renderTheme($view, $context, $blockNames);
    }

    public function dataGridColumnHeader(HeaderViewInterface $view, array $vars = []): string
    {
        $dataGridView = $view->getDataGridView();
        $blockNames = [
            "datagrid_{$dataGridView->getName()}_column_name_{$view->getName()}_header",
            "datagrid_{$dataGridView->getName()}_column_type_{$view->getType()}_header",
            "datagrid_column_name_{$view->getName()}_header",
            "datagrid_column_type_{$view->getType()}_header",
            "datagrid_{$dataGridView->getName()}_column_header",
            'datagrid_column_header',
        ];

        $context = [
            'header' => $view,
            'translation_domain' => $view->getAttribute('translation_domain'),
            'vars' => array_merge(
                $this->getDataGridVars($view->getDataGridView()),
                $vars
            )
        ];

        return $this->renderTheme($dataGridView, $context, $blockNames);
    }

    public function dataGridRowset(DataGridViewInterface $view, array $vars = []): string
    {
        $blockNames = [
            "datagrid_{$view->getName()}_rowset",
            'datagrid_rowset',
        ];

        $context = [
            'datagrid' => $view,
            'vars' => array_merge($this->getDataGridVars($view), $vars)
        ];

        return $this->renderTheme($view, $context, $blockNames);
    }

    public function dataGridColumnCell(CellViewInterface $view, array $vars = []): string
    {
        $dataGridView = $view->getDataGridView();
        $blockNames = [
            "datagrid_{$dataGridView->getName()}_column_name_{$view->getName()}_cell",
            "datagrid_{$dataGridView->getName()}_column_type_{$view->getType()}_cell",
            "datagrid_column_name_{$view->getName()}_cell",
            "datagrid_column_type_{$view->getType()}_cell",
            "datagrid_{$dataGridView->getName()}_column_cell",
            'datagrid_column_cell',
        ];

        $context = [
            'cell' => $view,
            'row_index' => $view->getAttribute('row'),
            'datagrid_name' => $dataGridView->getName(),
            'translation_domain' => $view->getAttribute('translation_domain'),
            'vars' => array_merge($this->getDataGridVars($dataGridView), $vars)
        ];

        return $this->renderTheme($dataGridView, $context, $blockNames);
    }

    public function dataGridColumnCellForm(CellViewInterface $view, array $vars = []): string
    {
        if (false === $view->hasAttribute('form')) {
            return '';
        }

        $dataGridView = $view->getDataGridView();
        $blockNames = [
            "datagrid_{$dataGridView->getName()}_column_name_{$view->getName()}_cell_form",
            "datagrid_{$dataGridView->getName()}_column_type_{$view->getType()}_cell_form",
            "datagrid_column_name_{$view->getName()}_cell_form",
            "datagrid_column_type_{$view->getType()}_cell_form",
            "datagrid_{$dataGridView->getName()}_column_cell_form",
            'datagrid_column_cell_form',
        ];

        $context = [
            'form' => $view->getAttribute('form'),
            'vars' => array_merge($this->getDataGridVars($view->getDataGridView()), $vars)
        ];

        return $this->renderTheme($dataGridView, $context, $blockNames);
    }

    public function dataGridColumnActionCellActionWidget(
        CellViewInterface $view,
        string $action,
        string $content,
        array $urlAttrs = [],
        array $fieldMappingValues = []
    ): string {
        $dataGridView = $view->getDataGridView();
        $blockNames = [
            "datagrid_{$dataGridView->getName()}_column_type_action_cell_action_{$action}",
            "datagrid_column_type_action_cell_action_{$action}",
            "datagrid_{$dataGridView->getName()}_column_type_action_cell_action",
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

        return $this->renderTheme($dataGridView, $context, $blockNames);
    }

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
     * @param DataGridViewInterface $dataGrid
     * @return array<TemplateWrapper>
     */
    private function getTemplates(DataGridViewInterface $dataGrid): array
    {
        $this->initThemes();

        $templates = [];

        $dataGridId = spl_object_id($dataGrid);
        if (true === array_key_exists($dataGridId, $this->themes)) {
            $templates[] = $this->themes[$dataGridId];
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

    private function getDataGridVars(DataGridViewInterface $dataGrid): array
    {
        return $this->themesVars[spl_object_id($dataGrid)] ?? [];
    }

    private function renderTheme(
        DataGridViewInterface $dataGrid,
        array $contextVars = [],
        array $availableBlocks = []
    ): string {
        $templates = $this->getTemplates($dataGrid);
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
