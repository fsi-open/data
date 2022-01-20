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
use FSi\Component\DataSource\Exception\DataSourceException;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class FieldAbstractType implements FieldTypeInterface
{
    /**
     * @var array<FieldExtensionInterface>
     */
    private array $extensions;

    /**
     * @param array<FieldExtensionInterface> $extensions
     */
    public function __construct(array $extensions)
    {
        array_walk($extensions, static function (FieldExtensionInterface $fieldTypeExtension): void {
            $found = array_reduce(
                $fieldTypeExtension::getExtendedFieldTypes(),
                static fn(bool $found, string $extendedFieldType): bool
                    => $found || true === is_a(static::class, $extendedFieldType, true),
                false
            );

            if (false === $found) {
                throw new DataSourceException(
                    sprintf(
                        'DataSource field extension of class %s does not extend field type %s',
                        get_class($fieldTypeExtension),
                        static::class
                    )
                );
            }
        });

        $this->extensions = $extensions;
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');
        $optionsResolver->setRequired('comparison');
        $optionsResolver->setAllowedTypes('comparison', 'string');
    }

    public function createField(DataSourceInterface $dataSource, string $name, array $options): FieldInterface
    {
        $optionsResolver = new OptionsResolver();

        $this->initOptions($optionsResolver);
        $optionsResolver->setDefault('name', $name);
        foreach ($this->extensions as $extension) {
            $extension->initOptions($optionsResolver, $this);
        }

        return new Field($dataSource, $this, $name, $optionsResolver->resolve($options));
    }

    public function createView(FieldInterface $field): FieldViewInterface
    {
        $view = new FieldView($field);

        $this->buildView($field, $view);
        foreach ($this->extensions as $extension) {
            $extension->buildView($field, $view);
        }

        return $view;
    }

    protected function buildView(FieldInterface $field, FieldViewInterface $view): void
    {
    }

    /**
     * @param mixed $data
     * @return bool
     */
    protected function isEmpty($data): bool
    {
        return ([] === $data) || ('' === $data) || (null === $data);
    }
}
