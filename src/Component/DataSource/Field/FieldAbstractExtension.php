<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

class FieldAbstractExtension implements FieldExtensionInterface
{
    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function getExtendedFieldTypes(): array
    {
        return [];
    }

    public function initOptions(FieldTypeInterface $field): void
    {
    }
}
