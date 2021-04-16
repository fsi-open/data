<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Ordering\Driver;

use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFieldInterface;
use FSi\Component\DataSource\Event\DriverEvent;

class DBALExtension extends DriverExtension
{
    public function getExtendedDriverTypes(): array
    {
        return [
            'doctrine-dbal'
        ];
    }

    public function preGetResult(DriverEvent\DriverEventArgs $event): void
    {
        $fields = $event->getFields();
        $sortedFields = $this->sortFields($fields);

        /** @var DBALDriver $driver */
        $driver = $event->getDriver();

        $qb = $driver->getQueryBuilder();
        foreach ($sortedFields as $fieldName => $direction) {
            $field = $fields[$fieldName];
            $qb->addOrderBy($this->getFieldName($field, $driver->getAlias()), $direction);
        }
    }

    private function getFieldName(DBALFieldInterface $field, string $alias): string
    {
        $name = $field->getOption('field');

        if (true === $field->getOption('auto_alias') && false === strpos($name, ".")) {
            $name = "$alias.$name";
        }

        return $name;
    }
}
