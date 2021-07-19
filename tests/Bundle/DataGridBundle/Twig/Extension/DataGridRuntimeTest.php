<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\Twig\Extension;

use FSi\Bundle\DataGridBundle\Twig\Extension\DataGridRuntime;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\StubTranslator;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\TwigRuntimeLoader;
use FSi\Bundle\DataGridBundle\Twig\Extension\DataGridExtension;
use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use FSi\Component\DataGrid\DataGridViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormRenderer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Template;
use Twig\TemplateWrapper;

class DataGridRuntimeTest extends TestCase
{
    private Environment $twig;
    private DataGridExtension $extension;
    private DataGridRuntime $runtime;
    private TwigRuntimeLoader $runtimeLoader;
    private TranslatorInterface $translator;

    public function setUp(): void
    {
        $loader = new FilesystemLoader([
            __DIR__ . '/../../../../../vendor/symfony/twig-bridge/Resources/views/Form',
            __DIR__ . '/../../../../../src/Bundle/DataGridBundle/Resources/views', // datagrid base theme
            __DIR__ . '/../../Resources/views', // templates used in tests
        ]);

        $this->translator = new StubTranslator();
        $twig = new Environment($loader);
        $twig->addExtension(new TranslationExtension($this->translator));
        $twig->addGlobal('global_var', 'global_value');

        $twigRendererEngine = new TwigRendererEngine(['form_div_layout.html.twig'], $twig);
        $renderer = new FormRenderer($twigRendererEngine);
        $twig->addExtension(new FormExtension());

        $this->twig = $twig;
        $this->extension = new DataGridExtension();
        $this->runtime = new DataGridRuntime(['datagrid.html.twig'], $this->translator, $twig);

        $this->runtimeLoader = new TwigRuntimeLoader([$renderer, $this->runtime]);
        $twig->addRuntimeLoader($this->runtimeLoader);
    }

    public function testInitRuntimeShouldThrowExceptionBecauseNotExistingTheme(): void
    {
        $this->twig->addExtension($this->extension);

        $this->runtime = new DataGridRuntime(
            ['this_is_not_valid_path.html.twig'],
            $this->translator,
            $this->twig
        );
        $this->runtimeLoader->replaceInstance($this->runtime);

        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage('Unable to find template "this_is_not_valid_path.html.twig"');

        $dataGridView = $this->getDataGridView('grid');
        $this->runtime->dataGrid($dataGridView);
    }

    public function testInitRuntimeWithValidPathToTheme(): void
    {
        $this->twig->addExtension($this->extension);
        self::assertNotNull($this->twig->load('datagrid.html.twig'));
    }

    public function testRenderDataGridWidget(): void
    {
        $this->twig->addExtension($this->extension);

        $dataGridView = $this->getDataGridView('grid');
        $dataGridView->method('getColumns')
            ->willReturn(['title' => $this->getColumnHeaderView($dataGridView, 'text', 'title', 'Title')]);

        $dataGridWithThemeView = $this->getDataGridView('grid_with_theme');
        $dataGridWithThemeView->method('getColumns')
            ->willReturn(['title' => $this->getColumnHeaderView($dataGridWithThemeView, 'text', 'title', 'Title')]);

        $html = $this->twig->render('datagrid/datagrid_widget_test.html.twig', [
            'datagrid' => $dataGridView,
            'datagrid_with_theme' => $dataGridWithThemeView,
        ]);

        self::assertSame($this->getExpectedHtml('datagrid/datagrid_widget_result.html'), $html);
    }

    public function testRenderColumnHeaderWidget(): void
    {
        $this->twig->addExtension($this->extension);

        $dataGridView = $this->getDataGridView('grid');
        $dataGridWithThemeView = $this->getDataGridView('grid_with_header_theme');

        $headerView = $this->getColumnHeaderView($dataGridView, 'text', 'title', 'title');
        $headerWithThemeView = $this->getColumnHeaderView($dataGridWithThemeView, 'text', 'title', 'title');

        $html = $this->twig->render('datagrid/header_widget_test.html.twig', [
            'grid_with_header_theme' => $dataGridWithThemeView,
            'header' => $headerView,
            'header_with_theme' => $headerWithThemeView,
        ]);

        self::assertSame($this->getExpectedHtml('datagrid/datagrid_header_widget_result.html'), $html);
    }

    public function testRenderCellWidget(): void
    {
        $this->twig->addExtension($this->extension);

        $dataGridView = $this->getDataGridView('grid');
        $dataGridWithThemeView = $this->getDataGridView('grid_with_header_theme');

        $cellView = $this->getColumnCellView($dataGridView, 'text', 'title', 'This is value 1');
        $cellWithThemeView = $this->getColumnCellView($dataGridWithThemeView, 'text', 'title', 'This is value 2');

        $html = $this->twig->render('datagrid/cell_widget_test.html.twig', [
            'grid_with_header_theme' => $dataGridWithThemeView,
            'cell' => $cellView,
            'cell_with_theme' => $cellWithThemeView,
        ]);

        self::assertSame($this->getExpectedHtml('datagrid/datagrid_cell_widget_result.html'), $html);
    }

    public function testRenderCellActionWidget(): void
    {
        $this->twig->addExtension($this->extension);

        $dataGridView = $this->getDataGridView('grid');
        $dataGridWithThemeView = $this->getDataGridView('grid_with_header_theme');

        $cellView = $this->getColumnCellView($dataGridView, 'actions', 'action', []);
        $cellWithThemeView = $this->getColumnCellView($dataGridWithThemeView, 'actions', 'action', []);

        $html = $this->twig->render('datagrid/action_cell_action_widget_test.html.twig', [
            'grid_with_header_theme' => $dataGridWithThemeView,
            'cell' => $cellView,
            'cell_with_theme' => $cellWithThemeView,
        ]);

        self::assertSame($this->getExpectedHtml('datagrid/action_cell_action_widget_result.html'), $html);
    }

    public function testDataGridRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);
        $this->twig->load('datagrid.html.twig');
        $template = $this->getTemplateMock();

        $template->method('hasBlock')
            ->withConsecutive(['datagrid_grid'], ['datagrid'])
            ->willReturnOnConsecutiveCalls(false, true);
        $template->method('hasBlock')->with()->willReturn(true);

        $dataGridView = $this->getDataGridView('grid');
        $this->runtime->setTheme($dataGridView, new TemplateWrapper($this->twig, $template));

        $template->expects(self::once())
            ->method('displayBlock')
            ->with('datagrid', [
                'datagrid' => $dataGridView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);

        $this->runtime->dataGrid($dataGridView);
    }

    public function testDataGridMultipleTemplates(): void
    {
        $this->twig->addExtension($this->extension);
        $this->twig->load('datagrid.html.twig');

        $this->runtime = new DataGridRuntime(
            ['datagrid.html.twig', 'datagrid/second_theme.html.twig'],
            $this->translator,
            $this->twig
        );
        $this->runtimeLoader->replaceInstance($this->runtime);
        $dataGridView = $this->getDataGridView('grid');

        $html = $this->runtime->dataGrid($dataGridView);
        self::assertSame('<h1>This is second datagrid theme</h1>', $html);
    }

    public function testDataGridHeaderRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);
        $this->twig->load('datagrid.html.twig');
        $template = $this->getTemplateMock();

        $template->method('hasBlock')
            ->withConsecutive(['datagrid_grid_header'], ['datagrid_header'])
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $dataGridView = $this->getDataGridView('grid');
        $dataGridView->method('getColumns')->willReturn([]);
        $this->runtime->setTheme($dataGridView, new TemplateWrapper($this->twig, $template));


        $template->expects(self::once())
            ->method('displayBlock')
            ->with('datagrid_header', [
                'headers' => [],
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);
        $this->runtime->dataGridHeader($dataGridView);
    }

    public function testDataGridColumnHeaderRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);
        $this->twig->load('datagrid.html.twig');
        $template = $this->getTemplateMock();

        $template->method('hasBlock')
            ->withConsecutive(
                ['datagrid_grid_column_name_title_header'],
                ['datagrid_grid_column_type_text_header'],
                ['datagrid_column_name_title_header'],
                ['datagrid_column_type_text_header'],
                ['datagrid_grid_column_header'],
                ['datagrid_column_header']
            )
            ->willReturnOnConsecutiveCalls(false, false, false, false, false, true);

        $dataGridView = $this->getDataGridView('grid');
        $this->runtime->setTheme($dataGridView, new TemplateWrapper($this->twig, $template));

        $headerView = $this->getColumnHeaderView($dataGridView, 'text', 'title', 'Title');
        $headerView->method('getAttribute')->with('translation_domain')->willReturn(null);

        $template->expects(self::once())
            ->method('displayBlock')
            ->with('datagrid_column_header', [
                'header' => $headerView,
                'translation_domain' => null,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);

        $this->runtime->dataGridColumnHeader($headerView);
    }

    public function testDataGridRowsetRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);
        $this->twig->load('datagrid.html.twig');
        $template = $this->getTemplateMock();

        $template->method('hasBlock')
            ->withConsecutive(['datagrid_grid_rowset'], ['datagrid_rowset'])
            ->willReturnOnConsecutiveCalls(false, true);

        $dataGridView = $this->getDataGridView('grid');
        $this->runtime->setTheme($dataGridView, new TemplateWrapper($this->twig, $template));

        $template->expects(self::once())
            ->method('displayBlock')
            ->with('datagrid_rowset', [
                'datagrid' => $dataGridView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);

        $this->runtime->dataGridRowset($dataGridView);
    }

    public function testDataGridColumnCellRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);
        $this->twig->load('datagrid.html.twig');
        $template = $this->getTemplateMock();

        $template->method('hasBlock')
            ->withConsecutive(
                ['datagrid_grid_column_name_title_cell'],
                ['datagrid_grid_column_type_text_cell'],
                ['datagrid_column_name_title_cell'],
                ['datagrid_column_type_text_cell'],
                ['datagrid_grid_column_cell'],
                ['datagrid_column_cell']
            )->willReturnOnConsecutiveCalls(false, false, false, false, false, true);

        $dataGridView = $this->getDataGridView('grid');
        $this->runtime->setTheme($dataGridView, new TemplateWrapper($this->twig, $template));
        $cellView = $this->getColumnCellView($dataGridView, 'text', 'title', 'Value 1');

        $cellView
            ->method('getAttribute')
            ->willReturnCallback(
                function ($key) {
                    if ('row' === $key) {
                        return 0;
                    }

                    return null;
                }
            );

        $template->expects(self::once())
            ->method('displayBlock')
            ->with('datagrid_column_cell', [
                'cell' => $cellView,
                'row_index' => 0,
                'datagrid_name' => 'grid',
                'translation_domain' => null,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);

        $this->runtime->dataGridColumnCell($cellView);
    }

    public function testDataGridColumnCellFormRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);
        $this->twig->load('datagrid.html.twig');
        $template = $this->getTemplateMock();

        $template->method('hasBlock')
            ->withConsecutive(
                ['datagrid_grid_column_name_title_cell_form'],
                ['datagrid_grid_column_type_text_cell_form'],
                ['datagrid_column_name_title_cell_form'],
                ['datagrid_column_type_text_cell_form'],
                ['datagrid_grid_column_cell_form'],
                ['datagrid_column_cell_form']
            )->willReturnOnConsecutiveCalls(false, false, false, false, false, true);

        $dataGridView = $this->getDataGridView('grid');
        $this->runtime->setTheme($dataGridView, new TemplateWrapper($this->twig, $template));

        $cellView = $this->getColumnCellView($dataGridView, 'text', 'title', 'Value 1');
        $cellView->method('hasAttribute')->with('form')->willReturn(true);
        $cellView->method('getAttribute')->with('form')->willReturn('form');

        $template->expects(self::once())
            ->method('displayBlock')
            ->with('datagrid_column_cell_form', [
                'form' => 'form',
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);

        $this->runtime->dataGridColumnCellForm($cellView);
    }

    public function testDataGridColumnActionCellActionRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);
        $this->twig->load('datagrid.html.twig');
        $template = $this->getTemplateMock();

        $template->method('hasBlock')
            ->withConsecutive(
                ['datagrid_grid_column_type_action_cell_action_edit'],
                ['datagrid_column_type_action_cell_action_edit'],
                ['datagrid_grid_column_type_action_cell_action'],
                ['datagrid_column_type_action_cell_action'],
            )->willReturnOnConsecutiveCalls(false, false, false, true);

        $dataGridView = $this->getDataGridView('grid');
        $this->runtime->setTheme($dataGridView, new TemplateWrapper($this->twig, $template));

        $cellView = $this->getColumnCellView($dataGridView, 'action', 'actions', []);
        $cellView->method('getAttribute')->with('translation_domain')->willReturn(null);

        $template->expects(self::once())
            ->method('displayBlock')
            ->with('datagrid_column_type_action_cell_action', [
                'cell' => $cellView,
                'action' => 'edit',
                'content' => 'content',
                'attr' => [],
                'translation_domain' => null,
                'field_mapping_values' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);

        $this->runtime->dataGridColumnActionCellActionWidget($cellView, 'edit', 'content');
    }

    /**
     * @param string $name
     * @return DataGridViewInterface&MockObject
     */
    private function getDataGridView(string $name): DataGridViewInterface
    {
        /** @var DataGridViewInterface&MockObject $dataGridView */
        $dataGridView = $this->getMockBuilder(DataGridViewInterface::class)->disableOriginalConstructor()->getMock();
        $dataGridView->method('getName')->willReturn($name);

        return $dataGridView;
    }

    /**
     * @param DataGridViewInterface $dataGridView
     * @param string $type
     * @param string $name
     * @param string|null $label
     * @return HeaderViewInterface&MockObject
     */
    private function getColumnHeaderView(
        DataGridViewInterface $dataGridView,
        string $type,
        string $name,
        ?string $label = null
    ): HeaderViewInterface {
        /** @var HeaderViewInterface&MockObject $column */
        $column = $this->createMock(HeaderViewInterface::class);
        $column->method('getType')->willReturn($type);
        $column->method('getLabel')->willReturn($label);
        $column->method('getName')->willReturn($name);
        $column->method('getDataGridView')->willReturn($dataGridView);

        return $column;
    }

    /**
     * @param DataGridViewInterface $dataGridView
     * @param string $type
     * @param string $name
     * @param mixed $value
     * @return CellViewInterface&MockObject
     */
    private function getColumnCellView(
        DataGridViewInterface $dataGridView,
        string $type,
        string $name,
        $value
    ): CellViewInterface {
        /** @var CellViewInterface&MockObject $column */
        $column = $this->createMock(CellViewInterface::class);
        $column->method('getType')->willReturn($type);
        $column->method('getValue')->willReturn($value);
        $column->method('getName')->willReturn($name);
        $column->method('getDataGridView')->willReturn($dataGridView);

        return $column;
    }

    private function getExpectedHtml(string $filename): string
    {
        $path = __DIR__ . '/../../Resources/views/expected/' . $filename;
        if (false === file_exists($path)) {
            throw new RuntimeException(sprintf('Invalid expected html file path "%s"', $path));
        }

        return file_get_contents($path);
    }

    /**
     * @return Template&MockObject
     */
    private function getTemplateMock(): Template
    {
        /** @var Template&MockObject $template */
        $template = $this->createMock(Template::class);

        return $template;
    }
}
