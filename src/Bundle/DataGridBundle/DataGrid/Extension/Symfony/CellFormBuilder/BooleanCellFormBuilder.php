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
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use function array_key_exists;

final class BooleanCellFormBuilder extends AbstractCellFormBuilder
{
    public static function getSupportedColumnTypes(): array
    {
        return [Boolean::class];
    }

    public function prepareFormFields(ColumnInterface $column, $object, array $formTypes, array $options): array
    {
        $fields = parent::prepareFormFields($column, $object, $formTypes, $options);

        foreach ($fields as &$field) {
            if (true === array_key_exists('choices', $field['options'])) {
                continue;
            }

            $field['options']['choices'] = [
                $column->getOption('false_value') => 0,
                $column->getOption('true_value') => 1,
            ];
        }

        return $fields;
    }

    protected function getDefaultFormType(): string
    {
        return ChoiceType::class;
    }
}
