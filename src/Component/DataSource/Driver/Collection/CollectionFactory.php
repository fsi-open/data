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
use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

use function get_class;
use function gettype;
use function is_object;
use function iterator_to_array;

class CollectionFactory implements DriverFactoryInterface
{
    /**
     * @var array<DriverExtensionInterface>
     */
    private $extensions;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @param array<DriverExtensionInterface> $extensions
     */
    public function __construct(array $extensions = [])
    {
        $this->extensions = $extensions;
        $this->optionsResolver = new OptionsResolver();
        $this->initOptions();
    }

    public function getDriverType(): string
    {
        return 'collection';
    }

    public function createDriver(array $options = []): DriverInterface
    {
        $options = $this->optionsResolver->resolve($options);

        return new CollectionDriver($this->extensions, $options['collection'], $options['criteria']);
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
