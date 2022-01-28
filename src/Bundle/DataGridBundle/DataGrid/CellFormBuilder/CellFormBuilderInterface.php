<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\CellFormBuilder;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use Symfony\Component\Form\FormTypeInterface;

interface CellFormBuilderInterface
{
    /**
     * @return array<class-string<ColumnTypeInterface>>
     */
    public static function getSupportedColumnTypes(): array;

    /**
     * @param ColumnInterface $column
     * @param mixed $data
     * @return array<string,mixed>
     */
    public function prepareFormData(ColumnInterface $column, $data): array;

    /**
     * @param ColumnInterface $column
     * @param array<string,mixed>|object $object
     * @param array<string,class-string<FormTypeInterface>> $formTypes
     * @param array<string,array<string,mixed>> $options
     * @return array<string,array{name:string,type:string,options:array<string,mixed>}>
     */
    public function prepareFormFields(ColumnInterface $column, $object, array $formTypes, array $options): array;
}
