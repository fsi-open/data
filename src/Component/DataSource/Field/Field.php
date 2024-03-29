<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\Field\Type\FieldTypeInterface;

use function array_key_exists;

final class Field implements FieldInterface
{
    private string $dataSourceName;
    private FieldTypeInterface $type;
    private string $name;
    /**
     * @var array<string,mixed>
     */
    private array $options;
    /**
     * @var mixed
     */
    private $parameter;
    private bool $dirty = true;

    /**
     * @param string $dataSourceName
     * @param FieldTypeInterface $type
     * @param string $name
     * @param array<string,mixed> $options
     */
    public function __construct(
        string $dataSourceName,
        FieldTypeInterface $type,
        string $name,
        array $options
    ) {
        $this->dataSourceName = $dataSourceName;
        $this->type = $type;
        $this->name = $name;
        $this->options = $options;
    }

    public function getType(): FieldTypeInterface
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDataSourceName(): string
    {
        return $this->dataSourceName;
    }

    public function getOption(string $name)
    {
        return $this->options[$name];
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function bindParameter($parameter): void
    {
        $this->setDirty();

        $this->parameter = $parameter;
    }

    public function getParameter()
    {
        return $this->parameter;
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function setDirty(bool $dirty = true): void
    {
        $this->dirty = $dirty;
    }
}
