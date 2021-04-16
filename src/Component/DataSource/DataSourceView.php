<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

use ArrayIterator;
use Countable;
use FSi\Component\DataSource\Exception\DataSourceViewException;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Util\AttributesContainer;
use IteratorAggregate;

use function array_key_exists;
use function array_walk;
use function count;
use function sprintf;

class DataSourceView extends AttributesContainer implements DataSourceViewInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array
     */
    private $otherParameters;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var ArrayIterator|null
     */
    private $iterator;

    /**
     * @var Countable&IteratorAggregate
     */
    private $result;

    public function __construct(DataSourceInterface $datasource)
    {
        $this->name = $datasource->getName();
        $this->parameters = $datasource->getParameters();
        $this->otherParameters = $datasource->getOtherParameters();
        $this->result = $datasource->getResult();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getAllParameters(): array
    {
        return array_merge($this->otherParameters, $this->parameters);
    }

    public function getOtherParameters(): array
    {
        return $this->otherParameters;
    }

    public function hasField(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    public function removeField(string $name): void
    {
        if (false === array_key_exists($name, $this->fields)) {
            return;
        }

        $this->fields[$name]->setDataSourceView(null);
        unset($this->fields[$name]);
        $this->iterator = null;
    }

    public function getField(string $name): FieldViewInterface
    {
        if (false === $this->hasField($name)) {
            throw new DataSourceViewException("There's no field with name \"{$name}\"");
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
    }

    public function addField(FieldViewInterface $fieldView): void
    {
        $name = $fieldView->getName();
        if (true === $this->hasField($name)) {
            throw new DataSourceViewException(
                "There's already field with name \"{$name}\" in datasourc \"{$this->name}\""
            );
        }

        $this->fields[$name] = $fieldView;
        $fieldView->setDataSourceView($this);
        $this->iterator = null;
    }

    public function setFields(array $fields): void
    {
        $this->clearFields();

        array_walk($fields, function (FieldViewInterface $field): void {
            $this->addField($field);
        });
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->fields);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->fields[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        throw new DataSourceViewException(
            sprintf("It's forbidden to set individual field's views on %s in array-like style", self::class)
        );
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        throw new DataSourceViewException(
            sprintf("It's forbidden to unset individual field's views on %s in array-like style", self::class)
        );
    }

    public function count(): int
    {
        return count($this->fields);
    }

    /**
     * @param integer $position
     */
    public function seek($position): void
    {
        $this->getIterator()->seek($position);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->getIterator()->current();
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->getIterator()->key();
    }

    public function next()
    {
        $this->getIterator()->next();
    }

    public function rewind()
    {
        $this->getIterator()->rewind();
    }

    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }

    public function getResult(): IteratorAggregate
    {
        return $this->result;
    }

    private function getIterator(): ArrayIterator
    {
        if (null === $this->iterator) {
            $this->iterator = new ArrayIterator($this->fields);
        }

        return $this->iterator;
    }
}
