<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

use Countable;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use IteratorAggregate;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function array_key_exists;
use function array_reduce;
use function get_class;
use function gettype;

class DataSource implements DataSourceInterface
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array<FieldTypeInterface>
     */
    private $fields = [];

    /**
     * @var array<DataSourceExtensionInterface>
     */
    private $extensions = [];

    /**
     * @var DataSourceView|null
     */
    private $view;

    /**
     * @var DataSourceFactoryInterface|null
     */
    private $factory;

    /**
     * @var int
     */
    private $maxResults;

    /**
     * @var int
     */
    private $firstResult;

    /**
     * Cache for methods that depends on fields data (cache is dropped whenever
     * any of fields is dirty, or fields have changed).
     *
     * @var array{
     *   parameters?: array,
     *   result?: array{maxresults: int|null, firstresult: int|null, result: Result}
     * }
     */
    private $cache = [];

    /**
     * Flag set as true when fields or their data is modifying, or even new
     * extension is added.
     *
     * @var bool
     */
    private $dirty = true;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct(DriverInterface $driver, string $name = 'datasource')
    {
        if (1 !== preg_match('/^[\w]+$/', $name)) {
            throw new DataSourceException('Name of data source may contain only word characters and digits.');
        }

        $this->driver = $driver;
        $this->name = $name;
        $this->eventDispatcher = new EventDispatcher();
        $driver->setDataSource($this);
    }

    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    public function addField(
        $name,
        ?string $type = null,
        ?string $comparison = null,
        array $options = []
    ): DataSourceInterface {
        if (true === $name instanceof FieldTypeInterface) {
            $field = $name;
            $name = $name->getName();

            if (null === $name) {
                throw new DataSourceException('Given field has no name set.');
            }
        } else {
            if (null === $type) {
                throw new DataSourceException('"type" can\'t be null.');
            }
            if (null === $comparison) {
                throw new DataSourceException('"comparison" can\'t be null.');
            }
            $field = $this->driver->getFieldType($type);
            $field->setName($name);
            $field->setComparison($comparison);
            $field->setOptions($options);
        }

        $this->dirty = true;
        $this->fields[$name] = $field;
        $field->setDataSource($this);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function removeField(string $name): void
    {
        if (false === array_key_exists($name, $this->fields)) {
            return;
        }

        unset($this->fields[$name]);
        $this->dirty = true;
    }

    public function getField(string $name): FieldTypeInterface
    {
        if (false === $this->hasField($name)) {
            throw new DataSourceException(
                sprintf('There\'s no field with name "%s" in DataSource "%s"', $name, $this->name)
            );
        }

        return $this->fields[$name];
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function clearFields(): void
    {
        $this->fields = [];
        $this->dirty = true;
    }

    public function bindParameters($parameters = []): void
    {
        $this->dirty = true;

        // PreBindParameters event.
        $event = new DataSourceEvent\ParametersEventArgs($this, $parameters);
        $this->eventDispatcher->dispatch($event, DataSourceEvents::PRE_BIND_PARAMETERS);
        $parameters = $event->getParameters();

        if (false === is_array($parameters)) {
            throw new DataSourceException(
                sprintf(
                    'Parameters after "datasource.pre_bind_parameters" event must be an array but are %s.',
                    true === is_object($parameters) ? get_class($parameters) : gettype($parameters)
                )
            );
        }

        foreach ($this->getFields() as $field) {
            $field->bindParameter($parameters);
        }

        // PostBindParameters event.
        $event = new DataSourceEvent\DataSourceEventArgs($this);
        $this->eventDispatcher->dispatch($event, DataSourceEvents::POST_BIND_PARAMETERS);
    }

    public function getResult(): Result
    {
        $this->checkFieldsClarity();

        if (
            true === array_key_exists('result', $this->cache)
            && $this->cache['result']['maxresults'] === $this->getMaxResults()
            && $this->cache['result']['firstresult'] === $this->getFirstResult()
        ) {
            return $this->cache['result']['result'];
        }

        // PreGetResult event.
        $event = new DataSourceEvent\DataSourceEventArgs($this);
        $this->eventDispatcher->dispatch($event, DataSourceEvents::PRE_GET_RESULT);

        $result = $this->driver->getResult($this->fields, $this->getFirstResult(), $this->getMaxResults());

        foreach ($this->getFields() as $field) {
            $field->setDirty(false);
        }

        // PostGetResult event.
        $event = new DataSourceEvent\ResultEventArgs($this, $result);
        $this->eventDispatcher->dispatch($event, DataSourceEvents::POST_GET_RESULT);
        $result = $event->getResult();

        // Creating cache.
        $this->cache['result'] = [
            'result' => $result,
            'firstresult' => $this->getFirstResult(),
            'maxresults' => $this->getMaxResults(),
        ];

        return $result;
    }

    public function setMaxResults(?int $max): DataSourceInterface
    {
        $this->dirty = true;
        $this->maxResults = $max;

        return $this;
    }

    public function setFirstResult(?int $first): DataSourceInterface
    {
        $this->dirty = true;
        $this->firstResult = $first;

        return $this;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    public function addExtension(DataSourceExtensionInterface $extension): void
    {
        $this->dirty = true;
        $this->extensions[] = $extension;

        foreach ($extension->loadSubscribers() as $subscriber) {
            $this->eventDispatcher->addSubscriber($subscriber);
        }

        foreach ($extension->loadDriverExtensions() as $driverExtension) {
            if (true === in_array($this->driver->getType(), $driverExtension->getExtendedDriverTypes(), true)) {
                $this->driver->addExtension($driverExtension);
            }
        }
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function createView(): DataSourceViewInterface
    {
        $view = new DataSourceView($this);

        // PreBuildView event.
        $event = new DataSourceEvent\ViewEventArgs($this, $view);
        $this->eventDispatcher->dispatch($event, DataSourceEvents::PRE_BUILD_VIEW);

        foreach ($this->fields as $key => $field) {
            $view->addField($field->createView());
        }

        $this->view = $view;

        // PostBuildView event.
        $event = new DataSourceEvent\ViewEventArgs($this, $view);
        $this->eventDispatcher->dispatch($event, DataSourceEvents::POST_BUILD_VIEW);

        return $this->view;
    }

    public function getParameters(): array
    {
        $this->checkFieldsClarity();
        if (true === array_key_exists('parameters', $this->cache)) {
            return $this->cache['parameters'];
        }

        $parameters = [];

        // PreGetParameters event.
        $event = new DataSourceEvent\ParametersEventArgs($this, $parameters);
        $this->eventDispatcher->dispatch($event, DataSourceEvents::PRE_GET_PARAMETERS);
        $parameters = $event->getParameters();

        foreach ($this->fields as $field) {
            $field->getParameter($parameters);
        }

        // PostGetParameters event.
        $event = new DataSourceEvent\ParametersEventArgs($this, $parameters);
        $this->eventDispatcher->dispatch($event, DataSourceEvents::POST_GET_PARAMETERS);
        $parameters = $event->getParameters();

        // Clearing parameters from empty values.
        $parameters = self::cleanData($parameters);

        $this->cache['parameters'] = $parameters;
        return $parameters;
    }

    public function getAllParameters(): array
    {
        if (null !== $this->factory) {
            return $this->factory->getAllParameters();
        }

        return $this->getParameters();
    }

    public function getOtherParameters(): array
    {
        if (null !== $this->factory) {
            return $this->factory->getOtherParameters($this);
        }

        return [];
    }

    public function setFactory(DataSourceFactoryInterface $factory): void
    {
        $this->factory = $factory;
    }

    public function getFactory(): ?DataSourceFactoryInterface
    {
        return $this->factory;
    }

    private static function cleanData(array $array): array
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            if (true === is_array($value)) {
                $newValue = self::cleanData($value);
                if (0 !== count($newValue)) {
                    $newArray[$key] = $newValue;
                }
            } elseif (is_scalar($value) && '' !== $value) {
                $newArray[$key] = $value;
            }
        }

        return $newArray;
    }

    /**
     * Checks if from last time some of data has changed, and if did, resets cache.
     */
    private function checkFieldsClarity(): void
    {
        // Initialize with dirty flag.
        $dirty = array_reduce($this->getFields(), static function (bool $dirty, FieldTypeInterface $field): bool {
            return $dirty || $field->isDirty();
        }, $this->dirty);

        // If flag was set to dirty, or any of fields was dirty, reset cache.
        if (true === $dirty) {
            $this->cache = [];
            $this->dirty = false;
        }
    }
}
