<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FSi\Bundle\DataSourceBundle\DataSourceBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\FixturesBundle\Controller\TestController;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\FixturesBundle\FixturesBundle;

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
            new DoctrineBundle(),
            new DataSourceBundle(),
            new FixturesBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->add('/test/{driver}', TestController::class, 'datasource_test');
    }

    protected function configureContainer(ContainerBuilder $configuration, LoaderInterface $loader): void
    {
        $configuration->loadFromExtension('framework', [
            'csrf_protection' => true,
            'default_locale' => 'en',
            'form' => ['csrf_protection' => true],
            'secret' => 'qwerty',
            'session' => [
                'storage_id' => 'session.storage.mock_file',
            ],
            'test' => true,
            'translator' => ['fallback' => 'en']
        ]);

        $configuration->loadFromExtension('twig', [
            'debug' => true,
            'strict_variables' => true
        ]);

        $configuration->loadFromExtension('fsi_data_source', [
            'yaml_configuration' => true
        ]);

        $configuration->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'user' => 'admin',
                'charset' => 'UTF8',
                'path' => '%kernel.project_dir%/var/data.sqlite',
                'logging' => false
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'mappings' => [
                    'datasource_bundle' => [
                        'mapping' => true,
                        'type' => 'xml',
                        'dir' => '%kernel.project_dir%/../../../Component/DataSource/Fixtures/doctrine',
                        'prefix' => 'Tests\FSi\Component\DataSource\Fixtures\Entity',
                        'is_bundle' => false
                    ]
                ]
            ]
        ]);

        $loader->load(__DIR__ . '/FixturesBundle/Resources/config/services.xml');
    }
}
