<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DependencyInjection\Compiler;

use FSi\Bundle\DataGridBundle\DataGrid\Extension\DependencyInjectionExtension;
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\Exception\DataGridException;
use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

use function is_a;
use function sprintf;

final class DataGridPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (true === $container->hasExtension('stof_doctrine_extensions')) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
            $loader->load('datagrid_gedmo.xml');
        }

        if (true === $container->hasDefinition(DependencyInjectionExtension::class)) {
            $columns = [];
            foreach ($container->findTaggedServiceIds('datagrid.column') as $serviceId => $tag) {
                $columns[] = new Reference($serviceId);
            }

            $columnExtensions = [];
            foreach ($container->findTaggedServiceIds('datagrid.column_extension') as $serviceId => $tag) {
                $columnExtensions[] = new Reference($serviceId);
            }

            foreach ($columns as $columnReference) {
                $columnDefinition = $container->getDefinition((string) $columnReference);
                $columnClass = $columnDefinition->getClass();
                if (null === $columnClass) {
                    throw new DataGridException(
                        sprintf(
                            'DataGrid column type service %s has no class',
                            (string) $columnReference
                        )
                    );
                }
                $columnTypeExtensionsReferences = [];
                foreach ($columnExtensions as $columnExtensionReference) {
                    $columnExtensionDefinition = $container->getDefinition((string) $columnExtensionReference);
                    $columnExtensionClass = $columnExtensionDefinition->getClass();
                    if (null === $columnExtensionClass) {
                        throw new DataGridException(
                            sprintf(
                                'DataGrid column extension service %s has no class',
                                (string) $columnExtensionReference
                            )
                        );
                    }
                    if (false === is_a($columnExtensionClass, ColumnTypeExtensionInterface::class, true)) {
                        throw new DataGridException(
                            sprintf(
                                'DataGrid column extension class %s must implement %s',
                                $columnExtensionClass,
                                ColumnTypeExtensionInterface::class
                            )
                        );
                    }
                    foreach ($columnExtensionClass::getExtendedColumnTypes() as $extendedColumnType) {
                        if (true === is_a($columnClass, $extendedColumnType, true)) {
                            $columnTypeExtensionsReferences[] = $columnExtensionReference;
                        }
                    }
                }

                $columnDefinition->replaceArgument('$columnTypeExtensions', $columnTypeExtensionsReferences);
            }
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
