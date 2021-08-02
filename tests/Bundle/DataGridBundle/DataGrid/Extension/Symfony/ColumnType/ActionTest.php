<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnType;

use FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnType\Action;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\Request;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ActionTest extends TestCase
{
    /**
     * @var RouterInterface&MockObject
     */
    private RouterInterface $router;
    /**
     * @var RequestStack&MockObject
     */
    private RequestStack $requestStack;
    private Action $columnType;

    public function testWrongActionsOptionType(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->columnType->createColumn($this->getDataGridMock(), 'action', ['actions' => 'boo']);
    }

    public function testInvalidActionInActionsOption(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $column = $this->columnType->createColumn(
            $this->getDataGridMock(),
            'action',
            ['field_mapping' => ['id'], 'actions' => ['edit' => 'asdasd']]
        );
        $this->columnType->createCellView($column, 1, (object) ['id' => 1]);
    }

    public function testRequiredActionInActionsOption(): void
    {
        $this->router->method('generate')
            ->with('foo', ['redirect_uri' => Request::RELATIVE_URI], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/test/bar?redirect_uri=' . urlencode(Request::ABSOLUTE_URI));

        $column = $this->columnType->createColumn(
            $this->getDataGridMock(),
            'action',
            [
                'field_mapping' => ['id', 'foo'],
                'actions' => [
                    'edit' => [
                        'route_name' => 'foo',
                        'absolute' => UrlGeneratorInterface::ABSOLUTE_PATH,
                    ],
                ],
            ]
        );

        self::assertSame(
            [
                'edit' => [
                    'content' => 'edit',
                    'field_mapping_values' => [
                        'id' => 1,
                        'foo' => 'bar',
                    ],
                    'url_attr' => [
                        'href' => '/test/bar?redirect_uri=http%3A%2F%2Fexample.com%2F%3Ftest%3D1%26test%3D2',
                    ],
                ],
            ],
            $this->columnType->createCellView($column, 1, (object) ['id' => 1, 'foo' => 'bar'])->getValue()
        );
    }

    public function testAvailableActionInActionsOption(): void
    {
        $this->router->expects(self::once())
            ->method('generate')
            ->with(
                'foo',
                ['foo' => 'bar', 'redirect_uri' => Request::RELATIVE_URI],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://fsi.pl/test/bar?redirect_uri=' . urlencode(Request::RELATIVE_URI));

        $column = $this->columnType->createColumn(
            $this->getDataGridMock(),
            'action',
            [
                'field_mapping' => ['id', 'foo'],
                'actions' => [
                    'edit' => [
                        'route_name' => 'foo',
                        'parameters_field_mapping' => ['foo' => 'foo'],
                        'absolute' => UrlGeneratorInterface::ABSOLUTE_URL,
                    ],
                ],
            ]
        );

        self::assertSame(
            [
                'edit' => [
                    'content' => 'edit',
                    'field_mapping_values' => [
                        'id' => 1,
                        'foo' => 'bar',
                    ],
                    'url_attr' => [
                        'href' => 'https://fsi.pl/test/bar?redirect_uri=' . urlencode(Request::RELATIVE_URI),
                    ],
                ],
            ],
            $this->columnType->createCellView($column, 1, (object) ['id' => 1, 'foo' => 'bar'])->getValue()
        );
    }

    public function testDisablingRedirectUri(): void
    {
        $this->router->expects(self::once())
            ->method('generate')
            ->with('foo', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/test/bar');

        $column = $this->columnType->createColumn(
            $this->getDataGridMock(),
            'action',
            [
                'field_mapping' => ['id', 'foo'],
                'actions' => [
                    'edit' => [
                        'route_name' => 'foo',
                        'absolute' => UrlGeneratorInterface::ABSOLUTE_PATH,
                        'redirect_uri' => false,
                    ],
                ],
            ]
        );

        self::assertSame(
            [
                'edit' => [
                    'content' => 'edit',
                    'field_mapping_values' => [
                        'id' => 1,
                        'foo' => 'bar',
                    ],
                    'url_attr' => [
                        'href' => '/test/bar',
                    ],
                ],
            ],
            $this->columnType->createCellView($column, 1, (object) ['id' => 1, 'foo' => 'bar'])->getValue()
        );
    }

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->method('getMasterRequest')->willReturn(new Request());
        $this->columnType = new Action($this->router, $this->requestStack, [new DefaultColumnOptionsExtension()]);
    }

    /**
     * @return DataGridInterface&MockObject
     */
    private function getDataGridMock(): DataGridInterface
    {
        $dataGrid = $this->createMock(DataGridInterface::class);
        $dataGrid->method('getDataMapper')
            ->willReturn(new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor()));

        return $dataGrid;
    }
}
