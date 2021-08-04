<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field;

use Doctrine\DBAL\Types\Types;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineAbstractField;
use FSi\Component\DataSource\Field\Type\BooleanTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Boolean extends DoctrineAbstractField implements BooleanTypeInterface
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

    public function getDBALType(): string
    {
        return Types::BOOLEAN;
    }
}
