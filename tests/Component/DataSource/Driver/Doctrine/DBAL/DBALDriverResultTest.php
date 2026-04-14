<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Countable;
use Doctrine\DBAL\Connection;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Extension\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Pagination\PaginationExtension;
use FSi\Component\DataSource\Result;
use Iterator;

use function preg_replace;

class DBALDriverResultTest extends TestBase
{
    private Connection $connection;

    public function testTableResultCount(): void
    {
        $dataSource = $this->getNewsDataSource();
        self::assertCount(100, $dataSource->getResult());
    }

    public function testDoubleCallToGetResultReturnSameResultSet(): void
    {
        $dataSource = $this->getNewsDataSource();
        self::assertSame($dataSource->getResult(), $dataSource->getResult());
    }

    public function testParametersFiltering(): void
    {
        $dataSource = $this->getNewsDataSource()->addField('title', 'text', ['comparison' => 'like']);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title' => 'title-1',
                ],
            ],
        ];
        $dataSource->bindParameters($parameters);

        // title-1, title-10-19, title-100
        self::assertCount(12, $dataSource->getResult());
    }

    public function testPaginatedResult(): void
    {
        $dataSource = $this->getNewsDataSource();
        $dataSource->addField('title', 'text', ['comparison' => 'like']);
        $dataSource->setMaxResults(10);

        $parameters = [
            $dataSource->getName() => [
                PaginationExtension::PARAMETER_PAGE => 2,
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title' => 'title-1',
                ],
            ],
        ];
        $dataSource->bindParameters($parameters);

        $result = $dataSource->getResult();

        // all result count
        self::assertCount(12, $result);
    }

    public function testSortingField(): void
    {
        $dataSource = $this->getNewsDataSource();
        $dataSource->addField('title', 'text', ['comparison' => 'like']);
        $dataSource->addField('content', 'text', ['comparison' => 'like']);
        $dataSource->setMaxResults(10);

        $parameters = [
            $dataSource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'content' => 'asc',
                    'title' => 'desc',
                ],
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title' => 'title-1',
                ],
            ],
        ];
        $dataSource->bindParameters($parameters);

        $result = $dataSource->getResult();
        self::assertEquals(
            'SELECT e.* FROM news e WHERE e.title LIKE :title ORDER BY e.content asc, e.title desc LIMIT 10',
            $this->queryLogger->getQueryBuilder()->getSQL()
        );
        self::assertInstanceOf(Countable::class, $result);
        self::assertCount(12, $result);
        self::assertCount(10, iterator_to_array($result));
        $iterator = $result->getIterator();
        self::assertInstanceOf(Iterator::class, $iterator);
        self::assertEquals('title-18', $iterator->current()['title']);
    }

    /**
     * Checks DataSource with DoctrineDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithJoins(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('n.id')
            ->addSelect('c.id category_id')
            ->from('news', 'n')
            ->join('n', 'category', 'c', 'n.category_id = c.id')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n',
        ];

        $dataSource = $dataSourceFactory
            ->createDataSource('doctrine-dbal', $driverOptions, 'name')
            ->addField('category', 'text', ['comparison' => 'eq', 'field' => 'c.name'])
            ->setMaxResults(8);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'category' => 'name-10',
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $result = $dataSource->getResult();

        self::assertCount(37, $result);
        self::assertCount(8, iterator_to_array($result));
    }

    /**
     * Checks DataSource with DBALDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithAggregates(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('c.*')
            ->addSelect('COUNT(n.id) newscount')
            ->from(self::TABLE_CATEGORY_NAME, 'c')
            ->leftJoin('c', 'news', 'n', 'n.category_id = c.id')
            ->groupBy('c.id')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'c',
        ];

        $dataSource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name');

        $dataSource
            ->addField('category', 'text', [
                'comparison' => 'like',
                'field' => 'c.name',
            ])
            ->addField('newscount', 'number', [
                'comparison' => 'gt',
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having',
            ])
            ->setMaxResults(3)
        ;

        $dataSource->bindParameters([
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => 3,
                ],
            ],
        ]);

        $result = $dataSource->getResult();
        self::assertCount(6, $result);
        self::assertCount(3, iterator_to_array($result));

        self::assertMatchesRegularExpression(
            '/^SELECT c\.\*, COUNT\(n\.id\) newscount FROM category c '
                . 'LEFT JOIN news n ON n\.category_id = c\.id '
                . 'GROUP BY c\.id HAVING newscount > :newscount '
                . 'LIMIT 3( OFFSET 0)?$/',
            $this->queryLogger->getQueryBuilder()->getSQL()
        );

        $dataSource->bindParameters([
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => ['newscount' => 0,],
            ],
        ]);

        $result = $dataSource->getResult();
        self::assertCount(10, $result);
        self::assertCount(3, iterator_to_array($result));

        self::assertMatchesRegularExpression(
            '/^SELECT c\.\*, COUNT\(n\.id\) newscount FROM category c '
                . 'LEFT JOIN news n ON n\.category_id = c\.id '
                . 'GROUP BY c\.id HAVING newscount > :newscount '
                . 'LIMIT 3( OFFSET 0)?$/',
            $this->queryLogger->getQueryBuilder()->getSQL()
        );

        $dataSource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name2');
        $dataSource
            ->addField('category', 'text', [
                'comparison' => 'like',
                'field' => 'c.name',
            ])
            ->addField('newscount', 'number', [
                'comparison' => 'between',
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having'
            ])
            ->setMaxResults(2)
        ;

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => [0, 1],
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $result = $dataSource->getResult();
        self::assertCount(3, $result);
        self::assertCount(2, iterator_to_array($result));

        self::assertMatchesRegularExpression(
            '/^SELECT c\.\*, COUNT\(n\.id\) newscount FROM category c '
                . 'LEFT JOIN news n ON n\.category_id = c\.id '
                . 'GROUP BY c\.id HAVING newscount BETWEEN :newscount_from AND :newscount_to '
                . 'LIMIT 2( OFFSET 0)?$/',
            $this->queryLogger->getQueryBuilder()->getSQL()
        );
    }

    /**
     * Tests if 'having' value of 'clause' option works properly in 'entity' field
     */
    public function testHavingClauseInEntityField(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('n')
            ->from(self::TABLE_NEWS_NAME, 'n')
            ->join('n', self::TABLE_CATEGORY_NAME, 'c', 'n.category_id = c.id')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n'
        ];

        $dataSource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name');
        $dataSource
            ->addField('category', 'number', [
                'comparison' => 'in',
                'clause' => 'having',
            ]);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'category' => [2, 3],
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->getResult();

        self::assertEquals(
            preg_replace('/\s+/', ' ', 'SELECT n
            FROM news n
                INNER JOIN category c ON n.category_id = c.id
            HAVING n.category IN (:dcValue1, :dcValue2)'),
            $this->queryLogger->getQueryBuilder()->getSQL()
        );
    }

    protected function setUp(): void
    {
        $this->connection = $this->getMemoryConnection();
        $this->loadTestData($this->connection);
    }

    /**
     * @return DataSourceInterface<array<string,mixed>>
     */
    private function getNewsDataSource(): DataSourceInterface
    {
        return $this->getDataSourceFactory()->createDataSource('doctrine-dbal', ['table' => 'news'], 'name');
    }
}
