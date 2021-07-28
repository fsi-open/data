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
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\DataGridFactory;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\Request;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Tests\FSi\Component\DataGrid\Fixtures\SimpleDataGridExtension;

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
    private DataGridFactory $dataGridFactory;

    public function testWrongActionsOptionType(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->dataGridFactory->createColumn($this->getDataGridMock(), Action::class, 'action', ['actions' => 'boo']);
    }

    public function testInvalidActionInActionsOption(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            Action::class,
            'action',
            ['field_mapping' => ['id'], 'actions' => ['edit' => 'asdasd']]
        );
        $this->dataGridFactory->createCellView($column, 1, (object) ['id' => 1]);
    }

    public function testRequiredActionInActionsOption(): void
    {
        $this->router->method('generate')
            ->with('foo', ['redirect_uri' => Request::RELATIVE_URI], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/test/bar?redirect_uri=' . urlencode(Request::ABSOLUTE_URI));

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            Action::class,
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
            $this->dataGridFactory->createCellView($column, 1, (object) ['id' => 1, 'foo' => 'bar'])->getValue()
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

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            Action::class,
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
            $this->dataGridFactory->createCellView($column, 1, (object) ['id' => 1, 'foo' => 'bar'])->getValue()
        );
    }

    public function testDisablingRedirectUri(): void
    {
        $this->router->expects(self::once())
            ->method('generate')
            ->with('foo', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/test/bar');

        $column = $this->dataGridFactory->createColumn(
            $this->getDataGridMock(),
            Action::class,
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
            $this->dataGridFactory->createCellView($column, 1, (object) ['id' => 1, 'foo' => 'bar'])->getValue()
        );
    }

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->method('getMasterRequest')->willReturn(new Request());
        $this->dataGridFactory = new DataGridFactory(
            [
                new SimpleDataGridExtension(
                    new DefaultColumnOptionsExtension(),
                    new Action($this->router, $this->requestStack)
                ),
            ],
            $this->createMock(DataMapperInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );
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
