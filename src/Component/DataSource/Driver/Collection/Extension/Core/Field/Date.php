<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Collection\CollectionAbstractField;
use FSi\Component\DataSource\Field\Type\DateTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Date extends CollectionAbstractField implements DateTypeInterface
{
    public function getId(): string
    {
        return 'date';
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        parent::initOptions($optionsResolver);

        $optionsResolver->setAllowedValues(
            'comparison',
            ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'notIn', 'between']
        );
    }
}
