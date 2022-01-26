<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\EventSubscriber;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\EventSubscriber\ConfigurationBuilder;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvent\PreBindParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

class ConfigurationBuilderTest extends TestCase
{
    /**
     * @var Kernel&MockObject
     */
    private MockObject $kernel;

    public function testReadConfigurationFromOneBundle(): void
    {
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $bundle = $this->createMock(BundleInterface::class);
                    $bundle->method('getPath')->willReturn(__DIR__ . '/../../../../Fixtures/FooBundle');

                    return [$bundle];
                }
            );

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('news');
        $dataSource->expects(self::once())
            ->method('addField')
            ->with('title', 'text', ['comparison' => 'like', 'label' => 'Title'])
        ;

        $this->createConfigurationBuilder(null)(new PreBindParameters($dataSource, []));
    }

    public function testReadConfigurationFromManyBundles(): void
    {
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $fooBundle = $this->createMock(BundleInterface::class);
                    $fooBundle->method('getPath')->willReturn(__DIR__ . '/../../../../Fixtures/FooBundle');

                    $barBundle = $this->createMock(BundleInterface::class);
                    $barBundle->method('getPath')->willReturn(__DIR__ . '/../../../../Fixtures/BarBundle');

                    return [$fooBundle, $barBundle];
                }
            );

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('news');

        $dataSource->expects(self::exactly(2))
            ->method('addField')
            ->withConsecutive(
                ['title', 'text', ['comparison' => 'like', 'label' => 'News Title']],
                ['author', 'text', ['comparison' => 'like']]
            );

        $this->createConfigurationBuilder(null)(new PreBindParameters($dataSource, []));
    }

    public function testMainConfigurationOverridesBundles(): void
    {
        $this->kernel->expects(self::never())->method('getBundles');

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('news');

        $dataSource->expects(self::exactly(2))
            ->method('addField')
            ->withConsecutive(
                ['title_short', 'text', ['label' => 'Short title']],
                ['created_at', 'date', ['label' => 'Created at']]
            )
        ;

        $mainDirectory = sprintf('%s/../../../../Resources/config/main_directory', __DIR__);
        $this->createConfigurationBuilder($mainDirectory)(new PreBindParameters($dataSource, []));
    }

    public function testBundleConfigUsedWhenNoFileFoundInMainDirectory(): void
    {
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $bundle = $this->createMock(BundleInterface::class);
                    $bundle->method('getPath')->willReturn(__DIR__ . '/../../../../Fixtures/FooBundle');

                    return [$bundle];
                }
            );

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('user');
        $dataSource->expects(self::once())->method('addField')->with('username', 'text', []);

        $mainDirectory = sprintf('%s/../../../../Resources/config/main_directory', __DIR__);
        $this->createConfigurationBuilder($mainDirectory)(new PreBindParameters($dataSource, []));
    }

    public function testExceptionThrownWhenMainConfigPathIsNotADirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"non existent directory" is not a directory!');

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('news');

        $this->createConfigurationBuilder('non existent directory')(new PreBindParameters($dataSource, []));
    }

    protected function setUp(): void
    {
        $kernelMockBuilder = $this->getMockBuilder(Kernel::class)->setConstructorArgs(['dev', true]);
        $kernelMockBuilder->onlyMethods(
            ['registerContainerConfiguration', 'registerBundles', 'getBundles', 'getContainer']
        );

        $this->kernel = $kernelMockBuilder->getMock();
    }

    private function createConfigurationBuilder(?string $mainConfigDirectory): ConfigurationBuilder
    {
        return new ConfigurationBuilder(
            $this->kernel,
            'Resources/config/datasource',
            $mainConfigDirectory
        );
    }
}
