<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColumnViewOptionsExtension extends ColumnAbstractTypeExtension
{
    public static function getExtendedColumnTypes(): array
    {
        return [ColumnAbstractType::class];
    }

    public function buildCellView(ColumnInterface $column, CellViewInterface $view): void
    {
        $view->setAttribute('translation_domain', $column->getOption('translation_domain'));
    }

    public function buildHeaderView(ColumnInterface $column, HeaderViewInterface $view): void
    {
        $view->setAttribute('translation_domain', $column->getOption('translation_domain'));
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'translation_domain' => 'messages',
        ]);

        $optionsResolver->setAllowedTypes('translation_domain', ['string', 'null']);
    }
}
