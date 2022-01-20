<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DependencyInjection;

use FSi\Component\DataSource\Driver\Collection\CollectionFieldInterface;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFieldInterface;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineFieldInterface;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Field\FieldExtensionInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FSIDataSourceExtension extends Extension
{
    /**
     * @param array<string,mixed> $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('datasource.xml');

        $this->registerDrivers($loader);

        if (isset($config['yaml_configuration']) && $config['yaml_configuration']) {
            $loader->load('datasource_yaml_configuration.xml');
            $container->setParameter(
                'datasource.yaml.main_config',
                $config['yaml_configuration']['main_configuration_directory']
            );
        }

        if (isset($config['twig']['enabled']) && $config['twig']['enabled']) {
            $loader->load('twig.xml');
            $container->setParameter('datasource.twig.template', $config['twig']['template']);
        }

        if (method_exists($container, 'registerForAutoconfiguration')) {
            $this->registerForAutoconfiguration($container);
        }
    }

    private function registerDrivers(LoaderInterface $loader): void
    {
        $loader->load('driver/collection.xml');
        $loader->load('driver/doctrine-orm.xml');
        $loader->load('driver/doctrine-dbal.xml');
    }

    private function registerForAutoconfiguration(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(FieldTypeInterface::class)->addTag('datasource.field');
        $container->registerForAutoconfiguration(FieldExtensionInterface::class)->addTag('datasource.field_extension');
        $container->registerForAutoconfiguration(DriverFactoryInterface::class)->addTag('datasource.driver.factory');
        $container->registerForAutoconfiguration(CollectionFieldInterface::class)
            ->addTag('datasource.driver.collection.field');
        $container->registerForAutoconfiguration(DoctrineFieldInterface::class)
            ->addTag('datasource.driver.doctrine-orm.field');
        $container->registerForAutoconfiguration(DBALFieldInterface::class)
            ->addTag('datasource.driver.doctrine-dbal.field');
        $container->registerForAutoconfiguration(DataSourceEventSubscriberInterface::class)
            ->addTag('datasource.event_subscriber', ['default_priority_method' => 'getPriority']);
    }
}
