<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\CellFormBuilder;

use FSi\Component\DataGrid\ColumnType\Number;
use FSi\Component\DataGrid\ColumnType\Text;
use FSi\Component\DataGrid\Gedmo\ColumnType\Tree;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class TextCellFormBuilder extends AbstractCellFormBuilder
{
    public static function getSupportedColumnTypes(): array
    {
        return [Text::class, Number::class, Tree::class];
    }

    protected function getDefaultFormType(): string
    {
        return TextType::class;
    }
}
