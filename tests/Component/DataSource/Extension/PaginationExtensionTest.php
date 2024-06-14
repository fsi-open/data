<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Extension;

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Event;
use FSi\Component\DataSource\Event\PostBuildView;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Extension\Pagination\EventSubscriber\PaginationPostBuildView;
use FSi\Component\DataSource\Extension\Pagination\EventSubscriber\PaginationPostGetParameters;
use FSi\Component\DataSource\Extension\Pagination\EventSubscriber\PaginationPreBindParameters;
use FSi\Component\DataSource\Extension\Pagination\PaginationExtension;
use FSi\Component\DataSource\Result;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\FSi\Component\DataSource\Fixtures\TestResult;
use Tests\FSi\Component\DataSource\Fixtures\UncountableTestResult;

use function array_key_exists;

final class PaginationExtensionTest extends TestCase
{
    /**
     * @return array<int,array{firstResult: int, maxResults: int, page:int|null, currentPage: int}>
     */
    public static function paginationCases(): array
    {
        return [
            [
                'firstResult' => 20,
                'maxResults' => 20,
                'page' => 2,
                'currentPage' => 2
            ],
            [
                'firstResult' => 20,
                'maxResults' => 0,
                'page' => null,
                'currentPage' => 1
            ],
            [
                'firstResult' => 0,
                'maxResults' => 20,
                'page' => null,
                'currentPage' => 1
            ],
        ];
    }

    /**
     * @dataProvider paginationCases
     */
    public function testPaginationExtensionWhenPageIsNotFirst(
        int $firstResult,
        int $maxResults,
        ?int $page,
        int $currentPage
    ): void {
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('datasource');
        $dataSource->method('getResult')->willReturn(new TestResult());
        $dataSource->method('getMaxResults')->willReturn($maxResults);
        $dataSource->method('getFirstResult')->willReturn($firstResult);

        $postGetParametersSubscriber = new PaginationPostGetParameters();
        $paginationPostBuildViewSubscriber = new PaginationPostBuildView();

        $event = new Event\PostGetParameters($dataSource, []);
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

        ($paginationPostBuildViewSubscriber)(new Event\PostBuildView($dataSource, $view));
    }

    public function testSetMaxResultsByBindRequest(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(Event\PreBindParameters::class, new PaginationPreBindParameters());
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

    public function testPaginationSkippedIfResultNotCountable(): void
    {
        $result = $this->createMock(Result::class);
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->expects(self::once())->method('getName')->willReturn('datasource');
        $dataSource->expects(self::once())->method('getResult')->willReturn($result);
        $dataSource->expects(self::once())->method('getMaxResults')->willReturn(null);

        $view = $this->createMock(DataSourceViewInterface::class);
        $view->expects(self::never())->method('setAttribute');

        $subscriber = new PaginationPostBuildView();
        ($subscriber)(new PostBuildView($dataSource, $view));
    }

    public function testExceptionThrownForUncountableResultsAndMaxResultsSet(): void
    {
        $this->expectException(DataSourceException::class);
        $this->expectExceptionMessage(
            'DataSource\'s "datasource" result of class'
            . ' "Tests\FSi\Component\DataSource\Fixtures\UncountableTestResult"'
            . ' is not countable, but has max results set'
        );

        $result = new UncountableTestResult();
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->expects(self::once())->method('getName')->willReturn('datasource');
        $dataSource->expects(self::once())->method('getResult')->willReturn($result);
        $dataSource->expects(self::once())->method('getMaxResults')->willReturn(10);

        $view = $this->createMock(DataSourceViewInterface::class);
        $view->expects(self::never())->method('setAttribute');

        $subscriber = new PaginationPostBuildView();
        ($subscriber)(new PostBuildView($dataSource, $view));
    }
}
