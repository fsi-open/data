<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class FieldAbstractExtension implements FieldExtensionInterface
{
    public function initOptions(OptionsResolver $optionsResolver, FieldTypeInterface $fieldType): void
    {
    }

    public function buildView(FieldInterface $field, FieldViewInterface $view): void
    {
    }
}
