<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Column;

use FSi\Component\DataGrid\Exception\DataGridColumnException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_merge;
use function sprintf;

trait ColumnTypeTrait
{
    /**
     * @var array<ColumnTypeExtensionInterface>
     */
    protected array $columnTypeExtensions;

    /**
     * @param ColumnInterface $column
     * @param int|string $index
     * @param array<string,mixed>|object $source
     * @return CellViewInterface
     */
    public function createCellView(ColumnInterface $column, $index, $source): CellViewInterface
    {
        $cellView = new CellView($column, $this->getValue($column, $source));
        $this->buildCellView($column, $cellView);
        foreach ($this->columnTypeExtensions as $extension) {
            $extension->buildCellView($column, $cellView, $index, $source);
        }

        return $cellView;
    }

    public function createHeaderView(ColumnInterface $column): HeaderViewInterface
    {
        $view = new HeaderView($column);

        $this->buildHeaderView($column, $view);
        foreach ($this->columnTypeExtensions as $extension) {
            $extension->buildHeaderView($column, $view);
        }

        return $view;
    }

    /**
     * @param string $name
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    protected function resolveOptions(string $name, array $options): array
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');
        $optionsResolver->setDefault(
            'field_mapping',
            static fn(Options $options, $previousValue) => $previousValue ?? [$options['name']]
        );
        $optionsResolver->setAllowedTypes('field_mapping', 'array');

        $this->initOptions($optionsResolver);
        foreach ($this->columnTypeExtensions as $extension) {
            $extension->initOptions($optionsResolver);
        }

        return $optionsResolver->resolve(array_merge(['name' => $name], $options));
    }

    /**
     * @param ColumnInterface $column
     * @param array<string,mixed>|object $object
     * @return mixed
     */
    protected function getValue(ColumnInterface $column, $object)
    {
        $values = [];
        if (false === $column->hasOption('field_mapping') || 0 === count($column->getOption('field_mapping'))) {
            throw new DataGridColumnException(
                sprintf('"field_mapping" option is missing in column "%s"', $column->getName())
            );
        }

        $dataMapper = $column->getDataGrid()->getDataMapper();
        foreach ($column->getOption('field_mapping') as $field) {
            $values[$field] = $dataMapper->getData($field, $object);
        }

        $value = $this->filterValue($column, $values);
        foreach ($this->columnTypeExtensions as $extension) {
            $value = $extension->filterValue($column, $value);
        }

        return $value;
    }

    /**
     * @param ColumnInterface $column
     * @param mixed $value
     * @return mixed
     */
    protected function filterValue(ColumnInterface $column, $value)
    {
        return $value;
    }

    abstract protected function initOptions(OptionsResolver $optionsResolver): void;

    abstract protected function buildHeaderView(ColumnInterface $column, HeaderViewInterface $view): void;

    abstract protected function buildCellView(ColumnInterface $column, CellViewInterface $view): void;
}
