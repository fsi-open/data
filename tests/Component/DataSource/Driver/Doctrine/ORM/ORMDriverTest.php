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
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Boolean;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Date;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\DateTime;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Entity;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Number;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Text;
use FSi\Component\DataSource\Driver\Doctrine\ORM\FieldType\Time;
use FSi\Component\DataSource\Driver\Doctrine\ORM\ORMFactory;
use FSi\Component\DataSource\Driver\Doctrine\ORM\ORMResult;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Event\PostGetParameters;
use FSi\Component\DataSource\Event\PreBindParameters;
use FSi\Component\DataSource\Extension;
use FSi\Component\DataSource\Extension\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Extension\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Ordering\Storage;
use FSi\Component\DataSource\Extension\Pagination\PaginationExtension;
use Iterator;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Tests\FSi\Component\DataSource\Fixtures\DoctrineQueryLogger;
use Tests\FSi\Component\DataSource\Fixtures\Entity\Category;
use Tests\FSi\Component\DataSource\Fixtures\Entity\Group;
use Tests\FSi\Component\DataSource\Fixtures\Entity\News;

final class ORMDriverTest extends TestCase
{
    private DoctrineQueryLogger $queryLogger;
    private EntityManager $em;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private ?Storage $orderingStorage = null;

    public function testNumberFieldComparingWithZero(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $dataSource = $dataSourceFactory
            ->createDataSource('doctrine-orm', ['entity' => News::class], 'datasource')
            ->addField('id', 'number', ['comparison' => 'eq']);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'id' => '0',
                ],
            ],
        ];
        $dataSource->bindParameters($parameters);
        $result = $dataSource->getResult();
        self::assertCount(0, $result);
    }

    public function testGeneralDoctrineDriverConfiguration(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();
        $dataSources = [];

        $dataSources[] = $dataSourceFactory->createDataSource(
            'doctrine-orm',
            ['entity' => News::class],
            'datasource'
        );

        $qb = $this->em->createQueryBuilder()->select('n')->from(News::class, 'n');

        $dataSources[] = $dataSourceFactory->createDataSource(
            'doctrine-orm',
            ['qb' => $qb, 'alias' => 'n'],
            'datasource2'
        );

        foreach ($dataSources as $dataSource) {
            $dataSource
                ->addField('title', 'text', ['comparison' => 'in'])
                ->addField('author', 'text', ['comparison' => 'like'])
                ->addField('created', 'datetime', [
                    'comparison' => 'between',
                    'field' => 'createDate',
                ])
                ->addField('category', 'entity', ['comparison' => 'eq'])
                ->addField('otherCategory', 'entity', ['comparison' => 'isNull'])
                ->addField('not_otherCategory', 'entity', ['comparison' => 'neq', 'field' => 'otherCategory'])
                ->addField('group', 'entity', ['comparison' => 'memberOf', 'field' => 'groups'])
                ->addField('not_group', 'entity', ['comparison' => 'notMemberOf', 'field' => 'groups'])
                ->addField('tags', 'text', ['comparison' => 'isNull', 'field' => 'tags'])
                ->addField('active', 'boolean', ['comparison' => 'eq'])
            ;

            $result1 = $dataSource->getResult();
            self::assertCount(100, $result1);
            $view1 = $dataSource->createView();

            // Checking if result cache works.
            self::assertSame($result1, $dataSource->getResult());

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                    ],
                ],
            ];
            $dataSource->bindParameters($parameters);
            $result2 = $dataSource->getResult();

            // Checking cache.
            self::assertSame($result2, $dataSource->getResult());

            self::assertCount(50, $result2);
            self::assertNotSame($result1, $result2);
            unset($result1, $result2);

            self::assertEquals($parameters, $dataSource->getBoundParameters());

            $dataSource->setMaxResults(20);
            $parameters = [
                $dataSource->getName() => [
                    PaginationExtension::PARAMETER_PAGE => 1,
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertCount(100, $result);
            self::assertCount(20, iterator_to_array($result));

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                        'title' => ['title44', 'title58'],
                        'created' => ['from' => '1970-02-05 01:00:00'],
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $view = $dataSource->createView();
            $result = $dataSource->getResult();
            self::assertCount(2, $result);

            // Checking entity fields. We assume that database has just been created so first category and first group
            // have ids equal to 1.
            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'group' => 1,
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertCount(25, $result);

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'not_group' => 1,
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertCount(75, $result);

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'category' => 1,
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertCount(20, $result);

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'not_otherCategory' => 1,
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertCount(40, $result);

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'group' => 1,
                        'category' => 1,
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertCount(5, $result);

            // Checking sorting.
            $parameters = [
                $dataSource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'title' => 'asc'
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertInstanceOf(ORMResult::class, $result);
            /** @var Iterator<int,News> $iterator */
            $iterator = $result->getIterator();
            self::assertEquals('title0', $iterator->current()->getTitle());

            // Checking sorting.
            $parameters = [
                $dataSource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'title' => 'desc',
                        'author' => 'asc'
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertInstanceOf(ORMResult::class, $result);
            /** @var Iterator<int,News> $iterator */
            $iterator = $result->getIterator();
            self::assertEquals('title99', $iterator->current()->getTitle());

            // checking isnull & notnull
            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'tags' => 'null'
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result1 = $dataSource->getResult();
            self::assertCount(50, $result1);
            $ids = [];

            foreach ($result1 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'tags' => 'notnull'
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result2 = $dataSource->getResult();
            self::assertCount(50, $result2);

            foreach ($result2 as $item) {
                self::assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2);

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'otherCategory' => 'null'
                    ],
                ],
            ];

            // checking isnull & notnull - field type entity
            $dataSource->bindParameters($parameters);
            $result1 = $dataSource->getResult();
            self::assertCount(50, $result1);
            $ids = [];

            foreach ($result1 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'otherCategory' => 'notnull'
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result2 = $dataSource->getResult();
            self::assertCount(50, $result2);

            foreach ($result2 as $item) {
                self::assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2);

            // checking - field type boolean
            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => null
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result1 = $dataSource->getResult();
            self::assertCount(100, $result1);

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => 1
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result2 = $dataSource->getResult();
            self::assertCount(50, $result2);
            $ids = [];

            foreach ($result2 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => 0
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result3 = $dataSource->getResult();
            self::assertCount(50, $result3);

            foreach ($result3 as $item) {
                self::assertNotContains($item->getId(), $ids);
            }

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => true
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result2 = $dataSource->getResult();
            self::assertCount(50, $result2);

            foreach ($result2 as $item) {
                self::assertContains($item->getId(), $ids);
            }

            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => false
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result3 = $dataSource->getResult();
            self::assertCount(50, $result3);

            foreach ($result3 as $item) {
                self::assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2, $result3);

            $parameters = [
                $dataSource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'active' => 'desc'
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertInstanceOf(ORMResult::class, $result);
            /** @var Iterator<int,News> $iterator */
            $iterator = $result->getIterator();
            self::assertTrue($iterator->current()->isActive());

            $parameters = [
                $dataSource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'active' => 'asc'
                    ],
                ],
            ];

            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
            self::assertInstanceOf(ORMResult::class, $result);
            /** @var Iterator<int,News> $iterator */
            $iterator = $result->getIterator();
            self::assertFalse($iterator->current()->isActive());

            //Test for clearing fields.
            $dataSource->clearFields();
            $parameters = [
                $dataSource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                    ],
                ],
            ];

            //Since there are no fields now, we should have all of entities.
            $dataSource->bindParameters($parameters);
            $result = $dataSource->getResult();
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

        $dataSource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $dataSource->addField('author', 'text', ['comparison' => 'like'])
            ->addField('category', 'text', [
                'comparison' => 'like',
                'field' => 'c.name',
            ])
            ->addField('group', 'text', [
                'comparison' => 'like',
                'field' => 'g.name',
            ]);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group0',
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        self::assertCount(25, $dataSource->getResult());

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group',
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        self::assertCount(100, $dataSource->getResult());

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group0',
                    'category' => 'category0',
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        self::assertCount(5, $dataSource->getResult());
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

        $dataSource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $dataSource
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
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => 3,
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->getResult();

        self::assertEquals(
            $this->queryLogger->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT c, COUNT(n) AS newscount FROM %s c INNER JOIN c.news n'
                    . ' GROUP BY c HAVING newscount > :newscount',
                Category::class
            )
        );

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => 0,
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->getResult();

        self::assertEquals(
            $this->queryLogger->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT c, COUNT(n) AS newscount FROM %s c INNER JOIN c.news n'
                    . ' GROUP BY c HAVING newscount > :newscount',
                Category::class
            )
        );

        $dataSource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource2');
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
            ]);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => [0, 1],
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->getResult();

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

        $dataSource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $dataSource
            ->addField('category', 'entity', [
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

    protected function setUp(): void
    {
        $config = ORMSetup::createConfiguration(true);
        $config->setMetadataDriverImpl(
            new SimplifiedXmlDriver(
                [__DIR__ . '/../../../Fixtures/doctrine' => 'Tests\\FSi\\Component\\DataSource\\Fixtures\\Entity'],
            )
        );
        $em = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            $config
        );
        $tool = new SchemaTool($em);
        $tool->createSchema([
            $em->getClassMetadata(News::class),
            $em->getClassMetadata(Category::class),
            $em->getClassMetadata(Group::class),
        ]);
        $this->load($em);
        $this->em = $em;
    }

    protected function tearDown(): void
    {
        $this->eventDispatcher = null;
        $this->orderingStorage = null;
    }

    /**
     * @return ORMFactory<object>
     */
    private function getDoctrineFactory(): ORMFactory
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManager')->willReturn($this->em);
        $managerRegistry->method('getManagerForClass')->willReturn($this->em);

        $fieldExtensions = [new FieldExtension($this->getOrderingStorage())];
        return new ORMFactory(
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
                new Extension\Ordering\EventSubscriber\ORMPreGetResult($this->getOrderingStorage())
            );
            $this->eventDispatcher->addListener(
                PreBindParameters::class,
                new Extension\Ordering\EventSubscriber\OrderingPreBindParameters($this->getOrderingStorage())
            );
            $this->eventDispatcher->addListener(
                PostGetParameters::class,
                new Extension\Ordering\EventSubscriber\OrderingPostGetParameters($this->getOrderingStorage())
            );
            $this->queryLogger = new DoctrineQueryLogger();
            $this->eventDispatcher->addListener(PreGetResult::class, $this->queryLogger, -1);
        }

        return $this->eventDispatcher;
    }

    private function getOrderingStorage(): Storage
    {
        if (null === $this->orderingStorage) {
            $this->orderingStorage = new Storage();
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
                $news->setOtherCategory($categories[($i + 1) % 5]);
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
