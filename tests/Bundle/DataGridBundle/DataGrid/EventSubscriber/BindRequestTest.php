<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\EventSubscriber;

use FSi\Bundle\DataGridBundle\DataGrid\EventSubscriber\BindRequest;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\Event\PreSubmitEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

use function class_exists;

final class BindRequestTest extends TestCase
{
    public function testPreBindDataWithoutRequestObject(): void
    {
        $event = new PreSubmitEvent($this->createMock(DataGridInterface::class), []);

        (new BindRequest())($event);

        self::assertSame([], $event->getData());
    }

    public function testPreBindDataPOST(): void
    {
        /** @var Request&MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getMethod')->willReturn('POST');

        if (true === class_exists(InputBag::class)) {
            /** @var ParameterBag<string,mixed> $requestBag */
            $requestBag = new InputBag();
        } else {
            $requestBag = new ParameterBag();
        }

        $requestBag->set('grid', ['foo' => 'bar']);
        $request->request = $requestBag;

        $grid = $this->createMock(DataGridInterface::class);
        $grid->expects(self::once())->method('getName')->willReturn('grid');

        $event = new PreSubmitEvent($grid, $request);

        (new BindRequest())($event);

        self::assertSame(['foo' => 'bar'], $event->getData());
    }

    public function testPreBindDataGET(): void
    {
        $request = new Request();
        $request->setMethod('GET');
        $request->query->set('grid', ['foo' => 'bar']);

        $grid = $this->createMock(DataGridInterface::class);
        $grid->expects(self::once())->method('getName')->willReturn('grid');

        $event = new PreSubmitEvent($grid, $request);

        (new BindRequest())($event);

        self::assertSame(['foo' => 'bar'], $event->getData());
    }
}
