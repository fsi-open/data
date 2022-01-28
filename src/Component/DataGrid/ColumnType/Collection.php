<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\ColumnType;

use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Collection extends ColumnAbstractType
{
    public function getId(): string
    {
        return 'collection';
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'collection_glue' => ' '
        ]);

        $optionsResolver->setAllowedTypes('collection_glue', 'string');
    }

    protected function filterValue(ColumnInterface $column, $value)
    {
        $value = (array) $value;
        foreach ($value as &$val) {
            if (false === is_array($val)) {
                continue;
            }

            $val = implode($column->getOption('collection_glue'), $val);
        }

        return $value;
    }
}
