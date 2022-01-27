<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\EventSubscriber;

use FSi\Component\DataGrid\Event\DataGridEventSubscriberInterface;
use FSi\Component\DataGrid\Event\PreSubmitEvent;
use FSi\Component\DataGrid\Exception\DataGridException;
use FSi\Bundle\DataGridBundle\HttpFoundation\RequestCompatibilityHelper;
use Symfony\Component\HttpFoundation\Request;

final class BindRequest implements DataGridEventSubscriberInterface
{
    public static function getPriority(): int
    {
        return 128;
    }

    public function __invoke(PreSubmitEvent $event): void
    {
        $dataGrid = $event->getDataGrid();
        $request = $event->getData();

        if (false === $request instanceof Request) {
            return;
        }

        $name = $dataGrid->getName();
        switch ($request->getMethod()) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                $data = RequestCompatibilityHelper::get($request->request, $name);
                break;
            case 'GET':
                $data = RequestCompatibilityHelper::get($request->query, $name);
                break;

            default:
                throw new DataGridException("The request method \"{$request->getMethod()}\" is not supported");
        }

        $event->setData($data);
    }
}
