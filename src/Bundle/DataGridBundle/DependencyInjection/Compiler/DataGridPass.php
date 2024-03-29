<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DependencyInjection\Compiler;

use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\DataGridFactory;
use FSi\Component\DataGrid\Exception\DataGridException;
use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function is_a;
use function sprintf;

final class DataGridPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->registerColumns($container);
        $this->registerEventSubscribers($container);
    }

    private function registerColumns(ContainerBuilder $container): void
    {
        $columns = [];
        foreach (array_keys($container->findTaggedServiceIds('datagrid.column')) as $serviceId) {
            $columns[] = new Reference($serviceId);
        }

        $columnExtensions = [];
        foreach (array_keys($container->findTaggedServiceIds('datagrid.column_extension')) as $serviceId) {
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

        $dataGridDefinition = $container->getDefinition(DataGridFactory::class);
        $dataGridDefinition->replaceArgument('$columnTypes', $columns);
    }

    private function registerEventSubscribers(ContainerBuilder $container): void
    {
        if (false === $container->hasDefinition('event_dispatcher')) {
            return;
        }

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
