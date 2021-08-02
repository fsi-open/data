<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use FSi\Component\DataGrid\Extension\Core\ColumnType\DateTime;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Number;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Text;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Entity;
use FSi\Component\DataGrid\Extension\Gedmo\ColumnType\Tree;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormExtension extends ColumnAbstractTypeExtension
{
    public static function getExtendedColumnTypes(): array
    {
        return [
            Text::class,
            Boolean::class,
            Number::class,
            DateTime::class,
            Entity::class,
            Tree::class,
        ];
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'editable' => false,
            'form_options' => [],
            'form_type' => [],
        ]);

        $optionsResolver->setAllowedTypes('editable', 'bool');
        $optionsResolver->setAllowedTypes('form_options', 'array');
        $optionsResolver->setAllowedTypes('form_type', 'array');
    }
}
