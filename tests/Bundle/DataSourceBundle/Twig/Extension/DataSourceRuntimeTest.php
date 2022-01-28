<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Twig\Extension;

use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceRuntime;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceView;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\Type\FieldTypeInterface;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\TwigRuntimeLoader;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\StubTranslator;
use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceExtension;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Template;
use Twig\TemplateWrapper;

/**
 * @author Stanislav Prokopov <stanislav.prokopov@gmail.com>
 */
final class DataSourceRuntimeTest extends TestCase
{
    private Environment $twig;
    private DataSourceExtension $extension;
    private DataSourceRuntime $runtime;
    private TwigRuntimeLoader $runtimeLoader;

    public function testInitRuntimeShouldThrowExceptionBecauseNotExistingTheme(): void
    {
        $this->twig->addExtension($this->extension);

        $this->runtime = new DataSourceRuntime(
            $this->createMock(RequestStack::class),
            $this->twig,
            $this->getRouter(),
            'this_is_not_valid_path.html.twig'
        );
        $this->runtimeLoader->replaceInstance($this->runtime);

        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage('Unable to find template "this_is_not_valid_path.html.twig"');

        $dataSource = $this->getDataSourceView('datasource');
        $this->runtime->dataSourceFilter($dataSource);
    }

    public function testInitRuntimeWithValidPathToTheme(): void
    {
        $this->twig->addExtension($this->extension);

        $dataSource = $this->getDataSourceView('datasource');
        self::assertSame('', $this->runtime->dataSourceFilter($dataSource));
    }

    public function testDataSourceFilterCount(): void
    {
        $this->twig->addExtension($this->extension);

        $fieldView1 = $this->createMock(FieldViewInterface::class);
        $fieldView1->expects(self::atLeastOnce())->method('hasAttribute')->with('form')->willReturn(true);
        $fieldView1->method('getName')->willReturn('field1');
        $field1 = $this->createMock(FieldInterface::class);

        $fieldView2 = $this->createMock(FieldViewInterface::class);
        $fieldView2->expects(self::atLeastOnce())->method('hasAttribute')->with('form')->willReturn(false);
        $fieldView2->method('getName')->willReturn('field2');
        $field2 = $this->createMock(FieldInterface::class);

        $fieldView3 = $this->createMock(FieldViewInterface::class);
        $fieldView3->expects(self::atLeastOnce())->method('hasAttribute')->with('form')->willReturn(true);
        $fieldView3->method('getName')->willReturn('field3');
        $field3 = $this->createMock(FieldInterface::class);

        $fieldType = $this->createMock(FieldTypeInterface::class);
        $fieldType->method('createField')
            ->withConsecutive(
                [self::isInstanceOf(DataSourceInterface::class), 'field1', []],
                [self::isInstanceOf(DataSourceInterface::class), 'field2', []],
                [self::isInstanceOf(DataSourceInterface::class), 'field3', []]
            )
            ->willReturnOnConsecutiveCalls($field1, $field2, $field3)
        ;
        $fieldType->method('createView')
            ->withConsecutive([$field1], [$field2], [$field3])
            ->willReturnOnConsecutiveCalls($fieldView1, $fieldView2, $fieldView3)
        ;
        $field1->method('getType')->willReturn($fieldType);
        $field2->method('getType')->willReturn($fieldType);
        $field3->method('getType')->willReturn($fieldType);

        $datasourceView = new DataSourceView('datasource', [$field1, $field2, $field3], [], []);

        self::assertEquals(2, $this->runtime->dataSourceFilterCount($datasourceView));
    }

    public function testDataSourceRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);

        $template = $this->getTemplateMock();
        $template->method('hasBlock')
            ->withConsecutive(['datasource_datasource_filter'], ['datasource_filter'])
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $datasourceView = $this->getDataSourceView('datasource');
        $this->runtime->setTheme($datasourceView, new TemplateWrapper($this->twig, $template));

        $template->expects(self::once())
            ->method('displayBlock')
            ->with('datasource_filter', [
                'datasource' => $datasourceView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true)
        ;

        $this->runtime->dataSourceFilter($datasourceView);
    }

    protected function setUp(): void
    {
        $loader = new FilesystemLoader([
            __DIR__ . '/../../../../../vendor/symfony/twig-bridge/Resources/views/Form',
            __DIR__ . '/../../../../../src/Bundle/DataSourceBundle/Resources/views', // datasource base theme
        ]);

        $twig = new Environment($loader);
        $twig->addExtension(new TranslationExtension(new StubTranslator()));
        $twig->addExtension(new FormExtension());
        $twig->addGlobal('global_var', 'global_value');

        $this->twig = $twig;
        $this->extension = new DataSourceExtension();
        $this->runtime = new DataSourceRuntime(
            $this->createMock(RequestStack::class),
            $twig,
            $this->getRouter(),
            'datasource.html.twig'
        );

        $this->runtimeLoader = new TwigRuntimeLoader([$this->runtime]);
        $this->twig->addRuntimeLoader($this->runtimeLoader);
    }

    /**
     * @return RouterInterface&MockObject
     */
    private function getRouter(): MockObject
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('some_route');

        return $router;
    }

    /**
     * @param string $name
     * @return DataSourceViewInterface<FieldViewInterface>&MockObject
     */
    private function getDataSourceView(string $name): MockObject
    {
        $datasourceView = $this->createMock(DataSourceViewInterface::class);
        $datasourceView->method('getName')->willReturn($name);

        return $datasourceView;
    }

    /**
     * @return Template&MockObject
     */
    private function getTemplateMock(): MockObject
    {
        return $this->createMock(Template::class);
    }
}
