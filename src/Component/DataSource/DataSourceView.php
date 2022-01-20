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
use FSi\Component\DataSource\Exception\DataSourceViewException;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Util\AttributesContainer;

use function array_key_exists;
use function array_walk;
use function count;
use function sprintf;

class DataSourceView extends AttributesContainer implements DataSourceViewInterface
{
    private string $name;

    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $parameters;

    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $otherParameters;

    /**
     * @var array<string,FieldViewInterface>
     */
    private array $fields = [];

    /**
     * @var ArrayIterator<string,FieldViewInterface>|null
     */
    private ?ArrayIterator $iterator = null;

    /**
     * @param string $name
     * @param array<FieldInterface> $fields
     * @param array<string, array<string, array<string, mixed>>> $parameters
     * @param array<string, array<string, array<string, mixed>>> $otherParameters
     */
    public function __construct(string $name, array $fields, array $parameters, array $otherParameters)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->otherParameters = $otherParameters;
        array_walk($fields, function (FieldInterface $field): void {
            $this->addField($field->getType()->createView($field));
        });
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

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->fields);
    }

    /**
     * @param string $offset
     * @return FieldViewInterface
     */
    public function offsetGet($offset): FieldViewInterface
    {
        if (false === $this->offsetExists($offset)) {
            throw new DataSourceViewException("There's no field with name \"{$offset}\"");
        }

        return $this->fields[$offset];
    }

    /**
     * @param string $offset
     * @param FieldViewInterface $value
     */
    public function offsetSet($offset, $value): void
    {
        throw new DataSourceViewException(
            sprintf("It's forbidden to set individual field's views on %s in array-like style", self::class)
        );
    }

    /**
     * @param string $offset
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

    public function current(): FieldViewInterface
    {
        return $this->getIterator()->current();
    }

    public function key(): string
    {
        return $this->getIterator()->key();
    }

    public function next(): void
    {
        $this->getIterator()->next();
    }

    public function rewind(): void
    {
        $this->getIterator()->rewind();
    }

    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }

    private function addField(FieldViewInterface $fieldView): void
    {
        $name = $fieldView->getName();
        if (true === $this->offsetExists($name)) {
            throw new DataSourceViewException(
                "There's already field with name \"{$name}\" in datasource \"{$this->name}\""
            );
        }

        $this->fields[$name] = $fieldView;
    }

    /**
     * @return ArrayIterator<string,FieldViewInterface>
     */
    private function getIterator(): ArrayIterator
    {
        if (null === $this->iterator) {
            $this->iterator = new ArrayIterator($this->fields);
        }

        return $this->iterator;
    }
}
