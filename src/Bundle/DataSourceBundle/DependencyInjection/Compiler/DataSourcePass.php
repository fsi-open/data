<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DependencyInjection\Compiler;

use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\FieldExtensionInterface;
use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;
use function array_map;
use function is_a;
use function sprintf;

final class DataSourcePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (false === $container->hasDefinition(DriverFactoryManager::class)) {
            return;
        }

        $this->registerDriverFactories($container);
        $this->registerEventSubscriber($container);
        $this->registerFields($container);
    }

    private function registerDriverFactories(ContainerBuilder $container): void
    {
        $driverFactories = [];
        $servicesIds = array_keys($container->findTaggedServiceIds('datasource.driver.factory'));
        foreach ($servicesIds as $serviceId) {
            $driverFactoryDefinition = $container->getDefinition($serviceId);
            $driverFactoryClass = $driverFactoryDefinition->getClass();
            if (null === $driverFactoryClass) {
                throw new DataSourceException(
                    "DataSource driver factory service {$serviceId} has no class"
                );
            }

            if (false === is_a($driverFactoryClass, DriverFactoryInterface::class, true)) {
                throw new DataSourceException(
                    sprintf(
                        'DataSource driver factory class %s must implement %s',
                        $driverFactoryClass,
                        DriverFactoryInterface::class
                    )
                );
            }

            $driverType = $driverFactoryClass::getDriverType();
            $driverFieldTypes = array_map(
                static fn ($id) => new Reference($id),
                array_keys($container->findTaggedServiceIds("datasource.driver.{$driverType}.field"))
            );

            $driverFactoryDefinition->replaceArgument('$fieldTypes', $driverFieldTypes);
            $driverFactories[] = $driverFactoryDefinition;
        }

        $container
            ->getDefinition(DriverFactoryManager::class)
            ->replaceArgument('$factories', $driverFactories)
        ;
    }

    private function registerFields(ContainerBuilder $container): void
    {
        $allFieldTypeExtensions = [];
        foreach ($container->findTaggedServiceIds('datasource.field_extension') as $serviceId => $tag) {
            $allFieldTypeExtensions[] = $serviceId;
        }

        $fieldTypes = [];
        foreach ($container->findTaggedServiceIds('datasource.field') as $serviceId => $tag) {
            $fieldTypes[] = new Reference($serviceId);
        }

        foreach ($fieldTypes as $fieldTypeReference) {
            $fieldTypeDefinition = $container->getDefinition((string) $fieldTypeReference);
            $columnClass = $fieldTypeDefinition->getClass();
            if (null === $columnClass) {
                throw new DataSourceException(
                    sprintf('DataSource field type service %s has no class', (string) $fieldTypeReference)
                );
            }

            $fieldTypeExtensionsReferences = [];
            foreach ($allFieldTypeExtensions as $fieldTypeExtensionReference) {
                $fieldTypeExtensionDefinition = $container->getDefinition((string) $fieldTypeExtensionReference);
                $fieldTypeExtensionClass = $fieldTypeExtensionDefinition->getClass();
                if (null === $fieldTypeExtensionClass) {
                    throw new DataSourceException(
                        sprintf(
                            'DataSource field extension service %s has no class',
                            (string) $fieldTypeExtensionReference
                        )
                    );
                }
                if (false === is_a($fieldTypeExtensionClass, FieldExtensionInterface::class, true)) {
                    throw new DataSourceException(
                        sprintf(
                            'DataSource field extension class %s must implement %s',
                            $fieldTypeExtensionClass,
                            FieldExtensionInterface::class
                        )
                    );
                }
                foreach ($fieldTypeExtensionClass::getExtendedFieldTypes() as $extendedFieldType) {
                    if (true === is_a($columnClass, $extendedFieldType, true)) {
                        $fieldTypeExtensionsReferences[] = $fieldTypeExtensionDefinition;
                    }
                }
            }

            $fieldTypeDefinition->replaceArgument('$extensions', $fieldTypeExtensionsReferences);
        }
    }

    private function registerEventSubscriber(ContainerBuilder $container): void
    {
        if (false === $container->hasDefinition('event_dispatcher')) {
            return;
        }

        $eventDispatcher = $container->getDefinition('event_dispatcher');
        foreach ($container->findTaggedServiceIds('datasource.event_subscriber') as $serviceId => $tag) {
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
