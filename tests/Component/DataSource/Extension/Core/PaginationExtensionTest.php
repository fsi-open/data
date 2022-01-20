<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Extension\Core;

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber\PaginationPostBuildView;
use FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber\PaginationPostGetParameters;
use FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber\PaginationPreBindParameters;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Event\DataSourceEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\FSi\Component\DataSource\Fixtures\TestResult;
use PHPUnit\Framework\TestCase;
use FSi\Component\DataSource\DataSourceViewInterface;

use function array_key_exists;

class PaginationExtensionTest extends TestCase
{
    /**
     * @return array<int,array{first_result:int,max_results:int,page:int|null,current_page:int}>
     */
    public function paginationCases(): array
    {
        return [
            [
                'first_result' => 20,
                'max_results' => 20,
                'page' => 2,
                'current_page' => 2
            ],
            [
                'first_result' => 20,
                'max_results' => 0,
                'page' => null,
                'current_page' => 1
            ],
            [
                'first_result' => 0,
                'max_results' => 20,
                'page' => null,
                'current_page' => 1
            ],
        ];
    }

    /**
     * First case of event (when page is not 1).
     * @dataProvider paginationCases
     */
    public function testPaginationExtension(int $firstResult, int $maxResults, ?int $page, int $currentPage): void
    {
        $datasource = $this->createMock(DataSourceInterface::class);
        $datasource->method('getName')->willReturn('datasource');
        $datasource->method('getResult')->willReturn(new TestResult());
        $datasource->method('getMaxResults')->willReturn($maxResults);
        $datasource->method('getFirstResult')->willReturn($firstResult);

        $postGetParametersSubscriber = new PaginationPostGetParameters();
        $paginationPostBuildViewSubscriber = new PaginationPostBuildView();

        $event = new DataSourceEvent\PostGetParameters($datasource, []);
        ($postGetParametersSubscriber)($event);

        if (null !== $page) {
            self::assertSame(
                [
                    'datasource' => [
                        PaginationExtension::PARAMETER_MAX_RESULTS => 20,
                        PaginationExtension::PARAMETER_PAGE => 2
                    ]
                ],
                $event->getParameters()
            );
        } else {
            $parameters = $event->getParameters();
            if (true === array_key_exists('datasource', $parameters)) {
                self::assertArrayNotHasKey(PaginationExtension::PARAMETER_PAGE, $parameters['datasource']);
            }
        }

        $view = $this->createMock(DataSourceViewInterface::class);
        $view->method('setAttribute')
            ->willReturnCallback(
                static function ($attribute, $value) use ($currentPage) {
                    if ('page' === $attribute) {
                        self::assertEquals($currentPage, $value);
                    }
                }
            )
        ;

        ($paginationPostBuildViewSubscriber)(new DataSourceEvent\PostBuildView($datasource, $view));
    }

    public function testSetMaxResultsByBindRequest(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(DataSourceEvent\PreBindParameters::class, new PaginationPreBindParameters());
        $driverFactory = new CollectionFactory($eventDispatcher, []);
        $driverFactoryManager = new DriverFactoryManager([$driverFactory]);
        $factory = new DataSourceFactory($eventDispatcher, $driverFactoryManager);
        $dataSource = $factory->createDataSource('collection', [], 'foo_source');

        $dataSource->bindParameters([
            'foo_source' => [
                PaginationExtension::PARAMETER_MAX_RESULTS => 105
            ]
        ]);

        self::assertEquals(105, $dataSource->getMaxResults());
    }
}
