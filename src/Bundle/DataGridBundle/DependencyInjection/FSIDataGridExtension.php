<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DependencyInjection;

use FSi\Component\DataGrid\Event\DataGridEventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use FSi\Component\DataGrid\DataGridExtensionInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;

final class FSIDataGridExtension extends Extension
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
        $loader->load('datagrid.xml');

        if (true === $config['yaml_configuration']['enabled']) {
            $loader->load('datagrid_yaml_configuration.xml');
            $container->setParameter(
                'datagrid.yaml.main_config',
                $config['yaml_configuration']['main_configuration_directory']
            );
        }

        if (true === $config['twig']['enabled']) {
            $loader->load('twig.xml');
            $container->setParameter('datagrid.twig.themes', $config['twig']['themes']);
        }

        $container->registerForAutoconfiguration(DataGridExtensionInterface::class)
            ->addTag('datagrid.extension');
        $container->registerForAutoconfiguration(ColumnTypeInterface::class)
            ->addTag('datagrid.column');
        $container->registerForAutoconfiguration(ColumnTypeExtensionInterface::class)
            ->addTag('datagrid.column_extension');
        $container->registerForAutoconfiguration(DataGridEventSubscriberInterface::class)
            ->addTag('datagrid.event_subscriber', ['default_priority_method' => 'getPriority']);
    }
}
