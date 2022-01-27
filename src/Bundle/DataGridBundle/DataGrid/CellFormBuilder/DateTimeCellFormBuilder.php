<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\CellFormBuilder;

use DateTimeInterface;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\ColumnType\DateTime;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use function array_key_exists;
use function is_numeric;
use function is_string;

final class DateTimeCellFormBuilder extends AbstractCellFormBuilder
{
    public static function getSupportedColumnTypes(): array
    {
        return [DateTime::class];
    }

    public function prepareFormFields(ColumnInterface $column, $object, array $formTypes, array $options): array
    {
        $fields = parent::prepareFormFields($column, $object, $formTypes, $options);

        foreach ($fields as &$field) {
            if (true === array_key_exists('input', $field['options'])) {
                continue;
            }

            $value = $column->getDataGrid()->getDataMapper()->getData($field['name'], $object);
            if (true === is_numeric($value)) {
                $field['options']['input'] = 'timestamp';
            } elseif (true === is_string($value)) {
                $field['options']['input'] = 'string';
            } elseif (true === $value instanceof DateTimeInterface) {
                $field['options']['input'] = 'datetime';
            }
        }

        return $fields;
    }

    protected function getDefaultFormType(): string
    {
        return DateTimeType::class;
    }
}
