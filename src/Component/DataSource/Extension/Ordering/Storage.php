<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Ordering;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Field\FieldInterface;

use function array_key_exists;
use function ksort;
use function usort;

final class Storage
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $dataSourceSortingParameters = [];
    /**
     * @var array<string, array{priority: int, ascending: bool}>
     */
    private array $fieldsSorting = [];

    /**
     * @param DataSourceInterface $dataSource
     * @param array<string, string> $orderingParameters
     */
    public function setDataSourceSortingParameters(DataSourceInterface $dataSource, array $orderingParameters): void
    {
        $this->dataSourceSortingParameters[$dataSource->getName()] = $orderingParameters;
    }

    /**
     * @param DataSourceInterface $dataSource
     * @return array<string,string>
     */
    public function getDataSourceSortingParameters(DataSourceInterface $dataSource): ?array
    {
        return $this->dataSourceSortingParameters[$dataSource->getName()] ?? null;
    }

    public function setFieldSorting(FieldInterface $field, int $priority, bool $ascending): void
    {
        $this->fieldsSorting[$this->getFieldKey($field)] = [
            'priority' => $priority,
            'ascending' => $ascending,
        ];
    }

    public function getFieldSortingPriority(FieldInterface $field): ?int
    {
        $fieldKey = $this->getFieldKey($field);

        if (false === array_key_exists($fieldKey, $this->fieldsSorting)) {
            return null;
        }

        return $this->fieldsSorting[$fieldKey]['priority'];
    }

    public function isFieldSortingAscending(FieldInterface $field): bool
    {
        $fieldKey = $this->getFieldKey($field);

        return $this->fieldsSorting[$fieldKey]['ascending'] ?? true;
    }

    /**
     * @param array<FieldInterface> $fields
     * @return array<string,string>
     */
    public function sortFields(array $fields): array
    {
        $sortedFields = [];
        $orderingDirection = [];

        $tmpFields = [];
        foreach ($fields as $field) {
            $priority = $this->getFieldSortingPriority($field);
            if (null !== $priority) {
                $tmpFields[$priority] = $field;
                $orderingDirection[$field->getName()] = $this->isFieldSortingAscending($field);
            }
        }
        ksort($tmpFields);
        foreach ($tmpFields as $field) {
            $fieldName = $field->getName();
            $sortedFields[$fieldName] = (true === $orderingDirection[$fieldName]) ? 'asc' : 'desc';
        }

        $tmpFields = $fields;
        usort($tmpFields, static function (FieldInterface $a, FieldInterface $b): int {
            switch (true) {
                case true === $a->hasOption('default_sort') && false === $b->hasOption('default_sort'):
                    return -1;

                case false === $a->hasOption('default_sort') && true === $b->hasOption('default_sort'):
                    return 1;

                case true === $a->hasOption('default_sort') && true === $b->hasOption('default_sort'):
                    switch (true) {
                        case true === $a->hasOption('default_sort_priority')
                            && false === $b->hasOption('default_sort_priority'):
                            return -1;

                        case false === $a->hasOption('default_sort_priority')
                            && true === $b->hasOption('default_sort_priority'):
                            return 1;

                        case true === $a->hasOption('default_sort_priority')
                            && true === $b->hasOption('default_sort_priority'):
                            return $b->getOption('default_sort_priority') <=> $a->getOption('default_sort_priority');
                    }

                    return 0;

                default:
                    return 0;
            }
        });

        foreach ($tmpFields as $field) {
            if (
                true === $field->hasOption('default_sort')
                && null !== $field->getOption('default_sort')
                && false === array_key_exists($field->getName(), $sortedFields)
            ) {
                $sortedFields[$field->getName()] = $field->getOption('default_sort');
            }
        }

        return $sortedFields;
    }

    private function getFieldKey(FieldInterface $field): string
    {
        return "{$field->getDataSource()->getName()}-{$field->getName()}";
    }
}
