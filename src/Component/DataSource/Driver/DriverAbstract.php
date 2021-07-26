<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver;

use Countable;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DriverEvent;
use FSi\Component\DataSource\Event\DriverEvents;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\FieldExtensionInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Result;
use IteratorAggregate;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function array_key_exists;

abstract class DriverAbstract implements DriverInterface
{
    /**
     * @var DataSourceInterface
     */
    protected $datasource;

    /**
     * @var array<DriverExtensionInterface>
     */
    protected $extensions = [];

    /**
     * @var array<FieldTypeInterface>
     */
    protected $fieldTypes = [];

    /**
     * @var array<string, array<FieldExtensionInterface>>
     */
    protected $fieldExtensions = [];

    /**
     * @var EventDispatcher|null
     */
    private $eventDispatcher;

    /**
     * @param array<DriverExtensionInterface> $extensions array with extensions
     * @throws DataSourceException
     */
    public function __construct(array $extensions = [])
    {
        foreach ($extensions as $extension) {
            if (false === $extension instanceof DriverExtensionInterface) {
                throw new DataSourceException(sprintf(
                    'Instance of %s expected, "%s" given.',
                    DriverExtensionInterface::class,
                    get_class($extension)
                ));
            }
            $this->addExtension($extension);
        }
    }

    public function setDataSource(DataSourceInterface $datasource): void
    {
        $this->datasource = $datasource;
    }

    public function getDataSource(): DataSourceInterface
    {
        return $this->datasource;
    }

    public function hasFieldType(string $type): bool
    {
        $this->initFieldType($type);

        return array_key_exists($type, $this->fieldTypes);
    }

    public function getFieldType(string $type): FieldTypeInterface
    {
        if (false === $this->hasFieldType($type)) {
            throw new DataSourceException(sprintf('Unsupported field type ("%s").', $type));
        }

        $field = clone $this->fieldTypes[$type];
        $field->initOptions();

        if (true === array_key_exists($type, $this->fieldExtensions)) {
            $field->setExtensions($this->fieldExtensions[$type]);
        }

        return $field;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function addExtension(DriverExtensionInterface $extension): void
    {
        if (false === in_array($this->getType(), $extension->getExtendedDriverTypes(), true)) {
            throw new DataSourceException(sprintf(
                'DataSource driver extension of class %s does not support %s driver',
                get_class($extension),
                $this->getType()
            ));
        }

        if (true === in_array($extension, $this->extensions, true)) {
            return;
        }

        $eventDispatcher = $this->getEventDispatcher();
        foreach ($extension->loadSubscribers() as $subscriber) {
            $eventDispatcher->addSubscriber($subscriber);
        }

        $this->extensions[] = $extension;
    }

    public function getResult(array $fields, ?int $first, ?int $max): Result
    {
        $this->initResult();

        // preGetResult event.
        $event = new DriverEvent\DriverEventArgs($this, $fields);
        $this->getEventDispatcher()->dispatch($event, DriverEvents::PRE_GET_RESULT);

        $result = $this->buildResult($fields, $first, $max);

        // postGetResult event.
        $event = new DriverEvent\ResultEventArgs($this, $fields, $result);
        $this->getEventDispatcher()->dispatch($event, DriverEvents::POST_GET_RESULT);

        return $event->getResult();
    }

    /**
     * Returns reference to EventDispatcher.
     */
    protected function getEventDispatcher(): EventDispatcher
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * Initialize building results i.e. prepare DQL query or initial XPath expression object.
     */
    abstract protected function initResult(): void;

    /**
     * Build result that will be returned by getResult.
     *
     * @param array<FieldTypeInterface> $fields
     * @param int|null $first
     * @param int|null $max
     * @return Result
     */
    abstract protected function buildResult(array $fields, ?int $first, ?int $max): Result;

    /**
     * Inits field for given type (including extending that type) and saves it as pattern for later cloning.
     */
    private function initFieldType(string $type): void
    {
        if (true === array_key_exists($type, $this->fieldTypes)) {
            return;
        }

        $typeInstance = null;
        foreach ($this->extensions as $extension) {
            if ($extension->hasFieldType($type)) {
                $typeInstance = $extension->getFieldType($type);
                break;
            }
        }

        if (null === $typeInstance) {
            return;
        }

        $this->fieldTypes[$type] = $typeInstance;

        $extensions = [];
        foreach ($this->extensions as $extension) {
            if ($extension->hasFieldTypeExtensions($type)) {
                $fieldExtensions = $extension->getFieldTypeExtensions($type);
                foreach ($fieldExtensions as $fieldExtension) {
                    $extensions[] = $fieldExtension;
                }
            }
        }

        $this->fieldExtensions[$type] = $extensions;
    }
}
