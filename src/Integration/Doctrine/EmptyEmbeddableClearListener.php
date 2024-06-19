<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Integration\Doctrine;

use Assert\Assertion;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function array_key_exists;
use function get_class;
use function is_object;
use function mb_strlen;
use function uksort;

/**
 * https://github.com/doctrine/doctrine2/issues/4568
 */
final class EmptyEmbeddableClearListener
{
    private ?PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = null;
    }

    public function clearEmpty(object $object, PostLoadEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();
        $classMetadata = $entityManager->getClassMetadata(get_class($object));
        if (false === $classMetadata instanceof ClassMetadata) {
            return;
        }

        $embeddedClasses = $this->getEmbeddedClasses($classMetadata);
        foreach ($embeddedClasses as $fieldName => $embeddedData) {
            /** @var class-string<object> $class */
            $class = $embeddedData['class'];
            $embeddedMeta = $entityManager->getClassMetadata($class);
            if (false === $embeddedMeta instanceof ClassMetadata) {
                continue;
            }

            $embeddedParentMeta = $classMetadata;
            $embeddedParentObject = $object;
            if (
                true === array_key_exists('declaredField', $embeddedData)
                && null !== $embeddedData['declaredField']
            ) {
                $embeddedParentObject = $this->getPropertyAccessor()->getValue(
                    $object,
                    $embeddedData['declaredField']
                );

                $embeddedParentData = $embeddedClasses[$embeddedData['declaredField']];
                /** @var class-string<object> $embeddedParentClass */
                $embeddedParentClass = $embeddedParentData['class'];
                $embeddedParentMeta = $entityManager->getClassMetadata($embeddedParentClass);
                if (false === $embeddedParentMeta instanceof ClassMetadata) {
                    continue;
                }

                $fieldName = $embeddedData['originalField'];
            }

            $embeddedObject = $embeddedParentMeta->getFieldValue($embeddedParentObject, $fieldName);
            if (
                true === is_object($embeddedParentObject)
                && true === $this->isEmpty($embeddedMeta, $embeddedObject)
            ) {
                $embeddedParentMeta->setFieldValue($embeddedParentObject, $fieldName, null);
            }
        }
    }

    /**
     * @param ClassMetadata<object> $metadata
     * @param object|null $embeddable
     * @return bool
     */
    private function isEmpty(ClassMetadata $metadata, ?object $embeddable): bool
    {
        if (null === $embeddable) {
            return true;
        }

        foreach ($metadata->getFieldNames() as $fieldName) {
            if (null !== $metadata->getFieldValue($embeddable, $fieldName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ClassMetadata<object> $classMetadata
     * @return array<string, mixed>
     */
    private function getEmbeddedClasses(ClassMetadata $classMetadata): array
    {
        $embeddedClasses = $classMetadata->embeddedClasses;
        uksort(
            $embeddedClasses,
            static fn(string $key1, string $key2): int => mb_strlen($key2) - mb_strlen($key1)
        );

        return $embeddedClasses;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
