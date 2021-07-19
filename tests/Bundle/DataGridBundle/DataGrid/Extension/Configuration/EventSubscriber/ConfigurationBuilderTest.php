<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\Extension\Configuration\EventSubscriber;

use FSi\Bundle\DataGridBundle\DataGrid\Extension\Configuration\EventSubscriber\ConfigurationBuilder;
use FSi\Component\DataGrid\DataGrid;
use FSi\Component\DataGrid\DataGridEvent;
use FSi\Component\DataGrid\DataGridEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class ConfigurationBuilderTest extends TestCase
{
    /**
     * @var Kernel&MockObject
     */
    private Kernel $kernel;
    private ConfigurationBuilder $subscriber;

    public function testSubscribedEvents(): void
    {
        self::assertEquals(
            $this->subscriber::getSubscribedEvents(),
            [DataGridEvents::PRE_SET_DATA => ['readConfiguration', 128]]
        );
    }

    public function testReadConfigurationFromOneBundle(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datagrid.yaml.main_config')
            ->willReturn(null)
        ;
        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $bundle = $this->createMock(Bundle::class);
                    $bundle->method('getPath')
                        ->willReturn(sprintf(__DIR__ . '/../../../../Fixtures/FooBundle'));

                    return [$bundle];
                }
            );

        $dataGrid = $this->getMockBuilder(DataGrid::class)->disableOriginalConstructor()->getMock();
        $dataGrid->method('getName')->willReturn('news');
        $dataGrid->expects(self::once())->method('addColumn')->with('id', 'number', ['label' => 'Identity']);

        $this->subscriber->readConfiguration(new DataGridEvent($dataGrid, []));
    }

    public function testReadConfigurationFromManyBundles(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datagrid.yaml.main_config')
            ->willReturn(null)
        ;

        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $fooBundle = $this->createMock(Bundle::class);
                    $fooBundle->method('getPath')
                        ->willReturn(sprintf('%s/../../../../Fixtures/FooBundle', __DIR__));

                    $barBundle = $this->createMock(Bundle::class);
                    $barBundle->method('getPath')
                        ->willReturn(sprintf('%s/../../../../Fixtures/BarBundle', __DIR__));

                    return [$fooBundle, $barBundle];
                }
            );

        $dataGrid = $this->getMockBuilder(DataGrid::class)->disableOriginalConstructor()->getMock();
        $dataGrid->method('getName')->willReturn('news');

        $dataGrid->expects(self::exactly(3))
            ->method('addColumn')
            ->withConsecutive(
                ['id', 'number', ['label' => 'ID']],
                ['title', 'text', []],
                ['author', 'text', []]
            )
        ;

        $this->subscriber->readConfiguration(new DataGridEvent($dataGrid, []));
    }

    public function testMainConfigurationOverridesBundles(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datagrid.yaml.main_config')
            ->willReturn(sprintf('%s/../../../../Resources/config/main_directory', __DIR__))
        ;

        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);
        $this->kernel->expects(self::never())->method('getBundles');
        $dataGrid = $this->getMockBuilder(DataGrid::class)->disableOriginalConstructor()->getMock();
        $dataGrid->method('getName')->willReturn('news');

        $dataGrid->expects(self::exactly(3))
            ->method('addColumn')
            ->withConsecutive(
                ['id', 'number', ['label' => 'ID']],
                ['title_short', 'text', ['label' => 'Short title']],
                ['created_at', 'date', ['label' => 'Created at']]
            );

        $this->subscriber->readConfiguration(new DataGridEvent($dataGrid, []));
    }

    public function testBundleConfigUsedWhenNoFileFoundInMainDirectory(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datagrid.yaml.main_config')
            ->willReturn(sprintf('%s/../../../../Resources/config/main_directory', __DIR__))
        ;

        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $bundle = $this->createMock(Bundle::class);
                    $bundle->method('getPath')
                        ->willReturn(sprintf('%s/../../../../Fixtures/FooBundle', __DIR__));

                    return [$bundle];
                }
            );

        $dataGrid = $this->getMockBuilder(DataGrid::class)->disableOriginalConstructor()->getMock();
        $dataGrid->method('getName')->willReturn('user');
        $dataGrid->expects(self::once())->method('addColumn')->with('username', 'text', []);

        $this->subscriber->readConfiguration(new DataGridEvent($dataGrid, []));
    }

    public function testExceptionThrownWhenMainConfigPathIsNotADirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"non existant directory" is not a directory!');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datagrid.yaml.main_config')
            ->willReturn('non existant directory')
        ;

        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);

        $dataGrid = $this->getMockBuilder(DataGrid::class)->disableOriginalConstructor()->getMock();
        $dataGrid->method('getName')->willReturn('news');

        $this->subscriber->readConfiguration(new DataGridEvent($dataGrid, []));
    }

    protected function setUp(): void
    {
        /** @var Kernel&MockObject $kernelMock */
        $kernelMock = $this->getMockBuilder(Kernel::class)
            ->setConstructorArgs(['dev', true])
            ->onlyMethods(['registerContainerConfiguration', 'registerBundles', 'getBundles', 'getContainer'])
            ->getMock();

        $this->kernel = $kernelMock;
        $this->subscriber = new ConfigurationBuilder($this->kernel);
    }
}
