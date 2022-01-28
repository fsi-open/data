<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\EventSubscriber;

use FSi\Component\DataSource\Event\PreBuildView;
use FSi\Component\DataSource\DataSourceEventSubscriberInterface;

use function array_merge;
use function asort;

final class DataSourcePostBuildView implements DataSourceEventSubscriberInterface
{
    public static function getPriority(): int
    {
        return 0;
    }

    public function __invoke(PreBuildView $event): void
    {
        $fields = $event->getFields();

        $positive = [];
        $negative = [];
        $neutral = [];

        $indexedViews = [];
        foreach ($fields as $field) {
            if ($field->hasOption('form_order')) {
                $order = $field->getOption('form_order');
                if ($order >= 0) {
                    $positive[$field->getName()] = $order;
                } else {
                    $negative[$field->getName()] = $order;
                }
                $indexedViews[$field->getName()] = $field;
            } else {
                $neutral[] = $field;
            }
        }
        asort($positive);
        asort($negative);

        $fields = [];
        foreach ($negative as $name => $order) {
            $fields[] = $indexedViews[$name];
        }

        $fields = array_merge($fields, $neutral);
        foreach ($positive as $name => $order) {
            $fields[] = $indexedViews[$name];
        }

        $event->setFields($fields);
    }
}
