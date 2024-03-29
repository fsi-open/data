<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use FSi\Component\DataSource\Driver\Collection\Exception\CollectionDriverException;
use FSi\Component\DataSource\Driver\Collection\FieldType\FieldTypeInterface;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function iterator_to_array;

/**
 * @template T
 * @template-implements DriverFactoryInterface<T>
 */
class CollectionFactory implements DriverFactoryInterface
{
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @var array<FieldTypeInterface>
     */
    private array $fieldTypes;

    private OptionsResolver $optionsResolver;

    public static function getDriverType(): string
    {
        return 'collection';
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param array<FieldTypeInterface> $fieldTypes
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, array $fieldTypes)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->fieldTypes = $fieldTypes;
        $this->optionsResolver = new OptionsResolver();
        $this->initOptions();
    }

    /**
     * @param array<string, mixed> $options
     * @return DriverInterface<T>
     */
    public function createDriver(array $options = []): DriverInterface
    {
        $options = $this->optionsResolver->resolve($options);

        return new CollectionDriver(
            $this->eventDispatcher,
            $this->fieldTypes,
            $options['collection'],
            $options['criteria']
        );
    }

    private function initOptions(): void
    {
        $this->optionsResolver->setDefaults([
            'criteria' => null,
            'collection' => [],
        ]);

        $this->optionsResolver->setAllowedTypes('collection', ['array', Traversable::class, Selectable::class]);
        $this->optionsResolver->setAllowedTypes('criteria', ['null', Criteria::class]);

        $this->optionsResolver->setNormalizer('collection', function (Options $options, $collection): Selectable {
            if (true === $collection instanceof Selectable) {
                return $collection;
            }

            if (true === $collection instanceof Traversable) {
                return new ArrayCollection(iterator_to_array($collection));
            }

            if (true === is_array($collection)) {
                return new ArrayCollection($collection);
            }

            throw new CollectionDriverException(
                sprintf(
                    'Provided collection type "%s" should be an instance of %s, %s or an array, but given %s',
                    get_class($collection),
                    Selectable::class,
                    Traversable::class,
                    is_object($collection) ? get_class($collection) : gettype($collection)
                )
            );
        });
    }
}
