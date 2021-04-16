<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface FieldExtensionInterface extends EventSubscriberInterface
{
    /**
     * @return array<string>
     */
    public function getExtendedFieldTypes(): array;

    /**
     * @param FieldTypeInterface $field
     */
    public function initOptions(FieldTypeInterface $field): void;
}
