<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\EventSubscriber;

use FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\EventSubscriber\BindRequest;
use FSi\Component\DataGrid\Event\PreBindDataEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use FSi\Component\DataGrid\DataGridInterface;

use function class_exists;

class BindRequestTest extends TestCase
{
    public function testPreBindDataWithoutRequestObject(): void
    {
        $event = new PreBindDataEvent($this->createMock(DataGridInterface::class), []);

        $subscriber = new BindRequest();

        $subscriber->preBindData($event);

        self::assertSame([], $event->getData());
    }

    public function testPreBindDataPOST(): void
    {
        /** @var Request&MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');

        if (true === class_exists(InputBag::class)) {
            $requestBag = new InputBag();
        } else {
            $requestBag = new ParameterBag();
        }
        /** @var ParameterBag $requestBag */
        $requestBag->set('grid', ['foo' => 'bar']);

        $request->request = $requestBag;

        $grid = $this->createMock(DataGridInterface::class);
        $grid->expects(self::once())->method('getName')->willReturn('grid');

        $event = new PreBindDataEvent($grid, $request);

        $subscriber = new BindRequest();

        $subscriber->preBindData($event);

        self::assertSame(['foo' => 'bar'], $event->getData());
    }

    public function testPreBindDataGET(): void
    {
        $request = new Request();
        $request->setMethod('GET');
        $request->query->set('grid', ['foo' => 'bar']);

        $grid = $this->createMock(DataGridInterface::class);
        $grid->expects(self::once())->method('getName')->willReturn('grid');

        $event = new PreBindDataEvent($grid, $request);

        $subscriber = new BindRequest();

        $subscriber->preBindData($event);

        self::assertSame(['foo' => 'bar'], $event->getData());
    }
}
