<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\CellFormBuilder;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Entity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

final class EntityCellFormBuilder extends AbstractCellFormBuilder
{
    public static function getSupportedColumnTypes(): array
    {
        return [Entity::class];
    }

    public function prepareFormData(ColumnInterface $column, $data): array
    {
        $formData = [];

        /** @var string $relationField */
        $relationField = $column->getOption('relation_field');
        if (true === array_key_exists($relationField, $data)) {
            $formData[$relationField] = $data[$relationField];
        }

        return $formData;
    }

    public function prepareFormFields(ColumnInterface $column, $object, array $formTypes, array $options): array
    {
        /** @var string $relationField */
        $relationField = $column->getOption('relation_field');
        $field = [
            'name' => $relationField,
            'type' => $formTypes[$relationField] ?? $this->getDefaultFormType(),
            'options' => $options[$relationField] ?? [],
        ];

        return [$relationField => $field];
    }

    protected function getDefaultFormType(): string
    {
        return EntityType::class;
    }
}
