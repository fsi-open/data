<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface FieldTypeInterface
{
    public function getType(): string;

    public function setName(string $name): void;

    public function getName(): ?string;

    public function setComparison(string $comparison): void;

    public function getComparison(): ?string;

    /**
     * @return array<string>
     */
    public function getAvailableComparisons(): array;

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void;

    public function hasOption(string $name): bool;

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption(string $name);

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * @param mixed $parameter
     */
    public function bindParameter($parameter): void;

    public function getParameter(array &$parameters): void;

    /**
     * @return mixed
     */
    public function getCleanParameter();

    /**
     * @param FieldExtensionInterface $extension
     */
    public function addExtension(FieldExtensionInterface $extension): void;

    /**
     * @param array<FieldExtensionInterface> $extensions
     */
    public function setExtensions(array $extensions): void;

    /**
     * @return array<FieldExtensionInterface>
     */
    public function getExtensions(): array;

    public function createView(): FieldViewInterface;

    public function isDirty(): bool;

    public function setDirty(bool $dirty = true): void;

    public function setDataSource(DataSourceInterface $datasource): void;

    public function getDataSource(): ?DataSourceInterface;

    /**
     * Sets the default options for this type.
     *
     * In order to access OptionsResolver in this method use $this->getOptionsResolver()
     * in inherited classes. This method is called in DataSource after loading the field type
     * from factory.
     */
    public function initOptions(): void;

    public function getOptionsResolver(): OptionsResolver;
}
