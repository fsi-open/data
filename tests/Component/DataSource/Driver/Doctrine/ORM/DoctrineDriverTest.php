<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Doctrine\ORM;

use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\ManagerRegistry;
use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineFactory;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field\Boolean;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field\Date;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field\DateTime;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field\Entity;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field\Number;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field\Text;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field\Time;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Paginator;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Event\DataSourceEvent\PostGetParameters;
use FSi\Component\DataSource\Event\DataSourceEvent\PreBindParameters;
use FSi\Component\DataSource\Extension\Core;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\FSi\Component\DataSource\Fixtures\Category;
use Tests\FSi\Component\DataSource\Fixtures\DoctrineQueryLogger;
use Tests\FSi\Component\DataSource\Fixtures\Group;
use Tests\FSi\Component\DataSource\Fixtures\News;
use Iterator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class DoctrineDriverTest extends TestCase
{
    private DoctrineQueryLogger $queryLogger;
    private EntityManager $em;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private ?Core\Ordering\Storage $orderingStorage = null;

    protected function setUp(): void
    {
        $config = Setup::createAnnotationMetadataConfiguration(
            [__DIR__ . '/../../../Fixtures'],
            true,
            null,
            null,
            false
        );
        $em = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $tool = new SchemaTool($em);
        $tool->createSchema([
            $em->getClassMetadata(News::class),
            $em->getClassMetadata(Category::class),
            $em->getClassMetadata(Group::class),
        ]);
        $this->load($em);
        $this->em = $em;
    }

    public function testNumberFieldComparingWithZero(): void
    {
        $datasourceFactory = $this->getDataSourceFactory();

        $datasource = $datasourceFactory
            ->createDataSource('doctrine-orm', ['entity' => News::class], 'datasource')
            ->addField('id', 'number', ['comparison' => 'eq']);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'id' => '0',
                ],
            ],
        ];
        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        self::assertCount(0, $result);
    }

    public function testGeneralDoctrineDriverConfiguration(): void
    {
        $datasourceFactory = $this->getDataSourceFactory();
        $datasources = [];

        $datasources[] = $datasourceFactory->createDataSource(
            'doctrine-orm',
            ['entity' => News::class],
            'datasource'
        );

        $qb = $this->em->createQueryBuilder()->select('n')->from(News::class, 'n');

        $datasources[] = $datasourceFactory->createDataSource(
            'doctrine-orm',
            ['qb' => $qb, 'alias' => 'n'],
            'datasource2'
        );

        foreach ($datasources as $datasource) {
            $datasource
                ->addField('title', 'text', ['comparison' => 'in'])
                ->addField('author', 'text', ['comparison' => 'like'])
                ->addField('created', 'datetime', [
                    'comparison' => 'between',
                    'field' => 'create_date',
                ])
                ->addField('category', 'entity', ['comparison' => 'eq'])
                ->addField('category2', 'entity', ['comparison' => 'isNull'])
                ->addField('not_category2', 'entity', ['comparison' => 'neq', 'field' => 'category2'])
                ->addField('group', 'entity', ['comparison' => 'memberOf', 'field' => 'groups'])
                ->addField('not_group', 'entity', ['comparison' => 'notMemberOf', 'field' => 'groups'])
                ->addField('tags', 'text', ['comparison' => 'isNull', 'field' => 'tags'])
                ->addField('active', 'boolean', ['comparison' => 'eq'])
            ;

            $result1 = $datasource->getResult();
            self::assertCount(100, $result1);
            $view1 = $datasource->createView();

            // Checking if result cache works.
            self::assertSame($result1, $datasource->getResult());

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                    ],
                ],
            ];
            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();

            // Checking cache.
            self::assertSame($result2, $datasource->getResult());

            self::assertCount(50, $result2);
            self::assertNotSame($result1, $result2);
            unset($result1, $result2);

            self::assertEquals($parameters, $datasource->getParameters());

            $datasource->setMaxResults(20);
            $parameters = [
                $datasource->getName() => [
                    PaginationExtension::PARAMETER_PAGE => 1,
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertCount(100, $result);
            self::assertCount(20, iterator_to_array($result));

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                        'title' => ['title44', 'title58'],
                        'created' => ['from' => new DateTimeImmutable(date('Y:m:d H:i:s', 35 * 24 * 60 * 60))],
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $view = $datasource->createView();
            $result = $datasource->getResult();
            self::assertCount(2, $result);

            // Checking entity fields. We assume that database has just been created so first category and first group
            // have ids equal to 1.
            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'group' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertCount(25, $result);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'not_group' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertCount(75, $result);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'category' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertCount(20, $result);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'not_category2' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertCount(40, $result);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'group' => 1,
                        'category' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertCount(5, $result);

            // Checking sorting.
            $parameters = [
                $datasource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'title' => 'asc'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertInstanceOf(Paginator::class, $result);
            /** @var Iterator<int,News> $iterator */
            $iterator = $result->getIterator();
            self::assertEquals('title0', $iterator->current()->getTitle());

            // Checking sorting.
            $parameters = [
                $datasource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'title' => 'desc',
                        'author' => 'asc'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertInstanceOf(Paginator::class, $result);
            /** @var Iterator<int,News> $iterator */
            $iterator = $result->getIterator();
            self::assertEquals('title99', $iterator->current()->getTitle());

            // checking isnull & notnull
            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'tags' => 'null'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result1 = $datasource->getResult();
            self::assertCount(50, $result1);
            $ids = [];

            foreach ($result1 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'tags' => 'notnull'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();
            self::assertCount(50, $result2);

            foreach ($result2 as $item) {
                self::assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'category2' => 'null'
                    ],
                ],
            ];

            // checking isnull & notnull - field type entity
            $datasource->bindParameters($parameters);
            $result1 = $datasource->getResult();
            self::assertCount(50, $result1);
            $ids = [];

            foreach ($result1 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'category2' => 'notnull'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();
            self::assertCount(50, $result2);

            foreach ($result2 as $item) {
                self::assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2);

            // checking - field type boolean
            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => null
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result1 = $datasource->getResult();
            self::assertCount(100, $result1);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => 1
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();
            self::assertCount(50, $result2);
            $ids = [];

            foreach ($result2 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => 0
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result3 = $datasource->getResult();
            self::assertCount(50, $result3);

            foreach ($result3 as $item) {
                self::assertNotContains($item->getId(), $ids);
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => true
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();
            self::assertCount(50, $result2);

            foreach ($result2 as $item) {
                self::assertContains($item->getId(), $ids);
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => false
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result3 = $datasource->getResult();
            self::assertCount(50, $result3);

            foreach ($result3 as $item) {
                self::assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2, $result3);

            $parameters = [
                $datasource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'active' => 'desc'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertInstanceOf(Paginator::class, $result);
            /** @var Iterator<int,News> $iterator */
            $iterator = $result->getIterator();
            self::assertTrue($iterator->current()->isActive());

            $parameters = [
                $datasource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'active' => 'asc'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertInstanceOf(Paginator::class, $result);
            /** @var Iterator<int,News> $iterator */
            $iterator = $result->getIterator();
            self::assertFalse($iterator->current()->isActive());

            //Test for clearing fields.
            $datasource->clearFields();
            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                    ],
                ],
            ];

            //Since there are no fields now, we should have all of entities.
            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            self::assertCount(100, $result);
        }
    }

    /**
     * Checks DataSource with DoctrineDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithJoins(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from(News::class, 'n')
            ->join('n.category', 'c')
            ->join('n.groups', 'g')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n'
        ];

        $datasource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $datasource->addField('author', 'text', ['comparison' => 'like'])
            ->addField('category', 'text', [
                'comparison' => 'like',
                'field' => 'c.name',
            ])
            ->addField('group', 'text', [
                'comparison' => 'like',
                'field' => 'g.name',
            ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group0',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        self::assertCount(25, $datasource->getResult());

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        self::assertCount(100, $datasource->getResult());

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group0',
                    'category' => 'category0',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        self::assertCount(5, $datasource->getResult());
    }

    public function testDoctrineDriverWithQueryWithAggregates(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->em->createQueryBuilder()
            ->select('c', 'COUNT(n) AS newscount')
            ->from(Category::class, 'c')
            ->join('c.news', 'n')
            ->groupBy('c')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'c'
        ];

        $datasource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $datasource
            ->addField('category', 'text', [
                'comparison' => 'like',
                'field' => 'c.name',
            ])
            ->addField('newscount', 'number', [
                'comparison' => 'gt',
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having'
            ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => 3,
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        self::assertEquals(
            $this->queryLogger->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT c, COUNT(n) AS newscount FROM %s c INNER JOIN c.news n'
                    . ' GROUP BY c HAVING newscount > :newscount',
                Category::class
            )
        );

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => 0,
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        self::assertEquals(
            $this->queryLogger->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT c, COUNT(n) AS newscount FROM %s c INNER JOIN c.news n'
                    . ' GROUP BY c HAVING newscount > :newscount',
                Category::class
            )
        );

        $datasource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource2');
        $datasource
            ->addField('category', 'text', [
                'comparison' => 'like',
                'field' => 'c.name',
            ])
            ->addField('newscount', 'number', [
                'comparison' => 'between',
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having'
            ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => [0, 1],
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        self::assertEquals(
            $this->queryLogger->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT c, COUNT(n) AS newscount FROM %s c INNER JOIN c.news n'
                    . ' GROUP BY c HAVING newscount BETWEEN :newscount_from AND :newscount_to',
                Category::class
            )
        );
    }

    public function testHavingClauseInEntityField(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from(News::class, 'n')
            ->join('n.category', 'c')
            ->groupBy('n')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n'
        ];

        $datasource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $datasource
            ->addField('category', 'entity', [
                'comparison' => 'in',
                'clause' => 'having',
            ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'category' => [2, 3],
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        self::assertEquals(
            $this->queryLogger->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT n FROM %s n INNER JOIN n.category c GROUP BY n HAVING n.category IN (:category)',
                News::class
            )
        );
    }

    public function testCreateDriverWithoutEntityAndQbOptions(): void
    {
        $factory = $this->getDoctrineFactory();
        $this->expectException(InvalidOptionsException::class);
        $factory->createDriver([]);
    }

    protected function tearDown(): void
    {
        $this->eventDispatcher = null;
        $this->orderingStorage = null;
    }

    private function getDoctrineFactory(): DoctrineFactory
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManager')->willReturn($this->em);
        $managerRegistry->method('getManagerForClass')->willReturn($this->em);

        $fieldExtensions = [new FieldExtension($this->getOrderingStorage())];
        return new DoctrineFactory(
            $managerRegistry,
            $this->getEventDispatcher(),
            [
                new Boolean($fieldExtensions),
                new Date($fieldExtensions),
                new DateTime($fieldExtensions),
                new Entity([]),
                new Number($fieldExtensions),
                new Text($fieldExtensions),
                new Time($fieldExtensions),
            ]
        );
    }

    private function getDataSourceFactory(): DataSourceFactory
    {
        $driverFactoryManager = new DriverFactoryManager([
            $this->getDoctrineFactory()
        ]);

        return new DataSourceFactory($this->getEventDispatcher(), $driverFactoryManager);
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
            $this->eventDispatcher->addListener(
                PreGetResult::class,
                new Core\Ordering\EventSubscriber\ORMPreGetResult($this->getOrderingStorage())
            );
            $this->eventDispatcher->addListener(
                PreBindParameters::class,
                new Core\Ordering\EventSubscriber\OrderingPreBindParameters($this->getOrderingStorage())
            );
            $this->eventDispatcher->addListener(
                PostGetParameters::class,
                new Core\Ordering\EventSubscriber\OrderingPostGetParameters($this->getOrderingStorage())
            );
            $this->queryLogger = new DoctrineQueryLogger();
            $this->eventDispatcher->addListener(PreGetResult::class, $this->queryLogger, -1);
        }

        return $this->eventDispatcher;
    }

    private function getOrderingStorage(): Core\Ordering\Storage
    {
        if (null === $this->orderingStorage) {
            $this->orderingStorage = new Core\Ordering\Storage();
        }

        return $this->orderingStorage;
    }

    private function load(EntityManager $em): void
    {
        // Injects 5 categories.
        $categories = [];
        for ($i = 0; $i < 5; $i++) {
            $category = new Category($i);
            $category->setName('category' . $i);
            $em->persist($category);
            $categories[] = $category;
        }

        // Injects 4 groups.
        $groups = [];
        for ($i = 0; $i < 4; $i++) {
            $group = new Group($i);
            $group->setName('group' . $i);
            $em->persist($group);
            $groups[] = $group;
        }

        // Injects 100 newses.
        for ($i = 0; $i < 100; $i++) {
            $news = new News($i);
            $news->setTitle('title' . $i);

            // Half of entities will have different author and content.
            if (0 === $i % 2) {
                $news->setAuthor('author' . $i . '@domain1.com');
                $news->setShortContent('Lorem ipsum.');
                $news->setContent('Content lorem ipsum.');
                $news->setTags('lorem ipsum');
                $news->setCategory2($categories[($i + 1) % 5]);
            } else {
                $news->setAuthor('author' . $i . '@domain2.com');
                $news->setShortContent('Dolor sit amet.');
                $news->setContent('Content dolor sit amet.');
                $news->setActive();
            }

            // Each entity has different date of creation and one of four hours of creation.
            $createDate = new DateTimeImmutable(date('Y:m:d H:i:s', $i * 24 * 60 * 60));
            $createTime = new DateTimeImmutable(date('H:i:s', (($i % 4) + 1 ) * 60 * 60));

            $news->setCreateDate($createDate);
            $news->setCreateTime($createTime);

            $news->setCategory($categories[$i % 5]);
            $news->getGroups()->add($groups[$i % 4]);

            $em->persist($news);
        }

        $em->flush();
    }
}
