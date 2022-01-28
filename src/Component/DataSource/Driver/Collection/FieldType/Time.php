<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection\FieldType;

use FSi\Component\DataSource\Field\Type\TimeTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Time extends AbstractFieldType implements TimeTypeInterface
{
    public function getId(): string
    {
        return 'time';
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
