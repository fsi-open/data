<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DependencyInjection\Compiler;

use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class DataGridPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (true === $container->hasExtension('stof_doctrine_extensions')) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
            $loader->load('datagrid_gedmo.xml');
        }

        if (true === $container->hasDefinition('datagrid.extension')) {
            $columns = [];
            foreach ($container->findTaggedServiceIds('datagrid.column') as $serviceId => $tag) {
                $alias = $tag[0]['alias'] ?? $serviceId;

                $columns[$alias] = new Reference($serviceId);
            }

            $container->getDefinition('datagrid.extension')->replaceArgument(0, $columns);

            $columnExtensions = [];
            foreach ($container->findTaggedServiceIds('datagrid.column_extension') as $serviceId => $tag) {
                $alias = $tag[0]['alias'] ?? $serviceId;

                $columnExtensions[$alias] = new Reference($serviceId);
            }

            $container->getDefinition('datagrid.extension')->replaceArgument(1, $columnExtensions);
        }

        if (true === $container->hasDefinition('event_dispatcher')) {
            $eventDispatcher = $container->getDefinition('event_dispatcher');

            foreach ($container->findTaggedServiceIds('datagrid.event_subscriber') as $serviceId => $tag) {
                $defaultPriorityMethod = $tag[0]['default_priority_method'] ?? null;
                $subscriberDefinition = $container->getDefinition($serviceId);
                $subscriberReflection = $container->getReflectionClass($subscriberDefinition->getClass());
                if (null === $subscriberReflection) {
                    throw new RuntimeException("Unable to reflect DataGrid event subscriber {$serviceId}");
                }
                $priority = 0;
                if (null !== $defaultPriorityMethod) {
                    $priorityMethodReflection = $subscriberReflection->getMethod($defaultPriorityMethod);
                    $priority = $priorityMethodReflection->invoke(null);
                }

                $subscriberInvokeMethodReflection = $subscriberReflection->getMethod('__invoke');
                $subscriberInvokeMethodEventArgumentReflection = $subscriberInvokeMethodReflection->getParameters()[0];
                $eventTypeReflection = $subscriberInvokeMethodEventArgumentReflection->getType();
                if (false === $eventTypeReflection instanceof ReflectionNamedType) {
                    throw new RuntimeException(
                        "Unable to reflect class name of the first argument of {$serviceId}::__invoke()"
                    );
                }
                $eventClass = $eventTypeReflection->getName();

                $eventDispatcher->addMethodCall('addListener', [$eventClass, new Reference($serviceId), $priority]);
            }
        }
    }
}
