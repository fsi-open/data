<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\View\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\Column\CellViewInterface;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Action;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Collection;
use FSi\Component\DataGrid\Extension\Core\ColumnType\DateTime;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Money;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Number;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Text;
use FSi\Component\DataGrid\Extension\Doctrine\ColumnType\Entity;
use FSi\Component\DataGrid\Extension\Gedmo\ColumnType\Tree;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColumnViewOptionsExtension extends ColumnAbstractTypeExtension
{
    public function buildCellView(ColumnInterface $column, CellViewInterface $view): void
    {
        $view->setAttribute('translation_domain', $column->getOption('translation_domain'));
    }

    public function buildHeaderView(ColumnInterface $column, HeaderViewInterface $view): void
    {
        $view->setAttribute('translation_domain', $column->getOption('translation_domain'));
    }

    public function getExtendedColumnTypes(): array
    {
        return [
            Action::class,
            Boolean::class,
            Text::class,
            DateTime::class,
            Number::class,
            Money::class,
            Tree::class,
            Entity::class,
            Collection::class,
        ];
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'translation_domain' => 'messages',
        ]);

        $optionsResolver->setAllowedTypes('translation_domain', ['string', 'null']);
    }
}
