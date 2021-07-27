<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultColumnOptionsExtension extends ColumnAbstractTypeExtension
{
    public function buildHeaderView(ColumnInterface $column, HeaderViewInterface $view): void
    {
        $view->setAttribute('label', $column->getOption('label'));
        $order = $column->getOption('display_order');
        if (null !== $order) {
            $view->setAttribute('display_order', $order);
        }
    }

    public function getExtendedColumnTypes(): array
    {
        return [ColumnAbstractType::class];
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'label' => function (Options $options, $previousValue) {
                return $previousValue ?? $options['name'];
            },
            'display_order' => null,
            'field_mapping' => function (Options $options, $previousValue) {
                return $previousValue ?? [$options['name']];
            },
        ]);
        $optionsResolver->setAllowedTypes('label', 'string');
        $optionsResolver->setAllowedTypes('field_mapping', 'array');
        $optionsResolver->setAllowedTypes('display_order', ['integer', 'null']);
    }
}
