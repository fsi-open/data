<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Collection\FieldType;

use FSi\Component\DataSource\Field\Type\TextTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Text extends AbstractFieldType implements TextTypeInterface
{
    public function getId(): string
    {
        return 'text';
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        parent::initOptions($optionsResolver);

        $optionsResolver->setAllowedValues(
            'comparison',
            ['eq', 'neq', 'in', 'notIn', 'contains']
        );
    }
}
