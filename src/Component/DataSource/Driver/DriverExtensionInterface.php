<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver;

use FSi\Component\DataSource\Field\FieldExtensionInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface DriverExtensionInterface
{
    /**
     * @return array<string>
     */
    public function getExtendedDriverTypes(): array;

    public function hasFieldType(string $type): bool;

    public function getFieldType(string $type): FieldTypeInterface;

    public function hasFieldTypeExtensions(string $type): bool;

    /**
     * @param string $type
     * @return array<FieldExtensionInterface>
     */
    public function getFieldTypeExtensions(string $type): array;

    /**
     * @return array<EventSubscriberInterface>
     */
    public function loadSubscribers(): array;
}
