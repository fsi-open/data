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
use FSi\Component\DataSource\Field\Type\BooleanTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Boolean extends CollectionAbstractField implements BooleanTypeInterface
{
    public function getId(): string
    {
        return 'boolean';
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        parent::initOptions($optionsResolver);

        $optionsResolver->setAllowedValues('comparison', ['eq']);
    }

    public function getPHPType(): ?string
    {
        return 'boolean';
    }
}
