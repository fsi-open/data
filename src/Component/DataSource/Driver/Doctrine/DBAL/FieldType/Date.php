<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL\FieldType;

use Doctrine\DBAL\Types\Types;
use FSi\Component\DataSource\Field\Type\DateTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Date extends AbstractFieldType implements DateTypeInterface
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
            ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'notIn', 'between', 'isNull']
        );
    }

    public function getDBALType(): ?string
    {
        return Types::DATE_IMMUTABLE;
    }
}
