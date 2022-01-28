<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event as DataSourceEvent;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\Event as FieldEvent;
use FSi\Component\DataSource\Field\FieldInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

use function array_key_exists;
use function array_reduce;
use function count;
use function get_class;
use function gettype;
use function is_array;
use function sprintf;

class DataSource implements DataSourceInterface
{
    private DriverInterface $driver;
    private EventDispatcherInterface $eventDispatcher;
    private ?DataSourceFactoryInterface $factory;
    private string $name;
    /**
     * @var array<FieldInterface>
     */
    private array $fields;
    private ?int $maxResults;
    private ?int $firstResult;
    /**
     * Cache for methods that depends on fields data (cache is dropped whenever
     * any of fields is dirty, or fields have changed).
     *
     * @var array{
     *   parameters?: array<string, array<string, array<string, mixed>>>,
     *   result?: array{max_results: int|null, first_result: int|null, result: Result}
     * }
     */
    private array $cache;
    /**
     * Flag set as true when fields or their data is modifying.
     *
     * @var bool
     */
    private bool $dirty;

    public function __construct(
        string $name,
        DataSourceFactoryInterface $factory,
        EventDispatcherInterface $eventDispatcher,
        DriverInterface $driver
    ) {
        if (1 !== preg_match('/^[\w]+$/', $name)) {
            throw new DataSourceException(
                "Name \"{$name}\" of data source may contain only word characters and digits."
            );
        }

        $this->name = $name;
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
        $this->driver = $driver;
        $this->fields = [];
        $this->maxResults = null;
        $this->firstResult = null;
        $this->cache = [];
        $this->dirty = true;
    }

    public function hasField(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    public function addField(
        string $name,
        string $type,
        array $options = []
    ): DataSourceInterface {
        $field = $this->driver->getFieldType($type)->createField($this, $name, $options);

        $this->dirty = true;
        $this->fields[$field->getName()] = $field;

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

    public function getField(string $name): FieldInterface
    {
        if (false === $this->hasField($name)) {
            throw new DataSourceException(
                "There is no field with name \"{$name}\" in DataSource \"{$this->name}\""
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

        $event = new DataSourceEvent\PreBindParameters($this, $parameters);
        $this->eventDispatcher->dispatch($event);
        $parameters = $event->getParameters();

        if (false === is_array($parameters)) {
            throw new DataSourceException(
                sprintf(
                    'Parameters after PreBindParameters event must be an array but are %s.',
                    true === is_object($parameters) ? get_class($parameters) : gettype($parameters)
                )
            );
        }

        if (true === array_key_exists($this->name, $parameters) && true === is_array($parameters[$this->name])) {
            $dataSourceFieldParameters = $parameters[$this->name][DataSourceInterface::PARAMETER_FIELDS] ?? [];

            foreach ($this->fields as $field) {
                $event = new FieldEvent\PreBindParameter(
                    $field,
                    $dataSourceFieldParameters[$field->getName()] ?? null
                );
                $this->eventDispatcher->dispatch($event);
                $parameter = $event->getParameter();

                $field->bindParameter($parameter);

                $event = new FieldEvent\PostBindParameter($field);
                $this->eventDispatcher->dispatch($event);
            }
        }

        $event = new DataSourceEvent\PostBindParameters($this);
        $this->eventDispatcher->dispatch($event);
    }

    public function getResult(): Result
    {
        $this->checkFieldsClarity();

        if (
            true === array_key_exists('result', $this->cache)
            && $this->cache['result']['max_results'] === $this->getMaxResults()
            && $this->cache['result']['first_result'] === $this->getFirstResult()
        ) {
            return $this->cache['result']['result'];
        }

        $result = $this->driver->getResult($this->fields, $this->getFirstResult(), $this->getMaxResults());

        foreach ($this->getFields() as $field) {
            $field->setDirty(false);
        }

        // Creating cache.
        $this->cache['result'] = [
            'result' => $result,
            'first_result' => $this->getFirstResult(),
            'max_results' => $this->getMaxResults(),
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

    public function createView(): DataSourceViewInterface
    {
        $event = new DataSourceEvent\PreBuildView($this->getFields());
        $this->eventDispatcher->dispatch($event);

        $view = new DataSourceView(
            $this->getName(),
            $event->getFields(),
            $this->getParameters(),
            $this->getOtherParameters()
        );

        $this->eventDispatcher->dispatch(new DataSourceEvent\PostBuildView($this, $view));

        return $view;
    }

    public function getParameters(): array
    {
        $this->checkFieldsClarity();
        if (true === array_key_exists('parameters', $this->cache)) {
            return $this->cache['parameters'];
        }

        $parameters = [];

        $event = new DataSourceEvent\PreGetParameters($this, $parameters);
        $this->eventDispatcher->dispatch($event);
        $parameters = $event->getParameters();

        $dataSourceName = $this->name;

        foreach ($this->fields as $field) {
            $event = new FieldEvent\PostGetParameter($field, $field->getParameter());
            $this->eventDispatcher->dispatch($event);

            if (false === array_key_exists($dataSourceName, $parameters)) {
                $parameters[$dataSourceName] = [];
            }
            if (false === array_key_exists(DataSourceInterface::PARAMETER_FIELDS, $parameters[$dataSourceName])) {
                $parameters[$dataSourceName][DataSourceInterface::PARAMETER_FIELDS] = [];
            }
            $parameters[$dataSourceName][DataSourceInterface::PARAMETER_FIELDS][$field->getName()]
                = $event->getParameter();
        }

        $event = new DataSourceEvent\PostGetParameters($this, $parameters);
        $this->eventDispatcher->dispatch($event);
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

    public function getFactory(): ?DataSourceFactoryInterface
    {
        return $this->factory;
    }

    /**
     * @param array<string,mixed> $array
     * @return array<string,mixed>
     */
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
        $dirty = array_reduce($this->getFields(), static function (bool $dirty, FieldInterface $field): bool {
            return $dirty || $field->isDirty();
        }, $this->dirty);

        // If flag was set to dirty, or any of fields was dirty, reset cache.
        if (true === $dirty) {
            $this->cache = [];
            $this->dirty = false;
        }
    }
}
