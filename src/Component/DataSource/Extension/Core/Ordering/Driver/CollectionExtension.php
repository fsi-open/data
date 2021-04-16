<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Ordering\Driver;

use FSi\Component\DataSource\Driver\Collection\CollectionDriver;
use FSi\Component\DataSource\Event\DriverEvent;

/**
 * Driver extension for ordering that loads fields extension.
 */
class CollectionExtension extends DriverExtension
{
    public function getExtendedDriverTypes(): array
    {
        return ['collection'];
    }

    public function preGetResult(DriverEvent\DriverEventArgs $event): void
    {
        $fields = $event->getFields();
        $sortedFields = $this->sortFields($fields);

        /** @var CollectionDriver $driver */
        $driver = $event->getDriver();
        $c = $driver->getCriteria();
        $orderings = $c->getOrderings();
        foreach ($sortedFields as $fieldName => $direction) {
            $field = $fields[$fieldName];
            $fieldName = true === $field->hasOption('field') ? $field->getOption('field') : $field->getName();
            $orderings[$fieldName] = strtoupper($direction);
        }
        $c->orderBy($orderings);
    }
}
