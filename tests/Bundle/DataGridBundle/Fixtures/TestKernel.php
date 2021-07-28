<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\Fixtures;

use FSi\Bundle\DataGridBundle\DataGridBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\FixturesBundle\Controller\TestController;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\FixturesBundle\FixturesBundle;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @return array<BundleInterface>
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new DataGridBundle(),
            new FixturesBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->add('/test', TestController::class);
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'csrf_protection' => true,
            'default_locale' => 'en',
            'form' => ['csrf_protection' => true],
            'secret' => 'qwerty',
            'session' => [
                'storage_id' => 'session.storage.mock_file',
            ],
            'test' => true,
            'translator' => ['fallback' => 'en'],
        ]);

        $c->loadFromExtension('twig', [
            'debug' => true,
        ]);

        $c->loadFromExtension('fsi_data_grid', [
            'yaml_configuration' => true,
        ]);

        $loader->load(__DIR__ . '/FixturesBundle/Resources/config/services.xml');
    }
}
