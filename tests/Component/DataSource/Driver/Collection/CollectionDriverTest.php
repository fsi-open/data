<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Collection;

use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Collection\CollectionResult;
use FSi\Component\DataSource\Driver\Collection\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Collection\FieldType\Boolean;
use FSi\Component\DataSource\Driver\Collection\FieldType\Date;
use FSi\Component\DataSource\Driver\Collection\FieldType\DateTime;
use FSi\Component\DataSource\Driver\Collection\FieldType\Number;
use FSi\Component\DataSource\Driver\Collection\FieldType\Text;
use FSi\Component\DataSource\Driver\Collection\FieldType\Time;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Event\PostGetParameters;
use FSi\Component\DataSource\Event\PreBindParameters;
use FSi\Component\DataSource\Extension;
use FSi\Component\DataSource\Extension\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Pagination\PaginationExtension;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\FSi\Component\DataSource\Fixtures\Entity\Category;
use Tests\FSi\Component\DataSource\Fixtures\Entity\Group;
use Tests\FSi\Component\DataSource\Fixtures\Entity\News;

class CollectionDriverTest extends TestCase
{
    private EntityManager $em;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private ?Extension\Ordering\Storage $orderingStorage = null;

    public function testComparingWithZero(): void
    {
        $dataSource = $this->prepareArrayDataSource()->addField('id', 'number', ['comparison' => 'eq']);

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

    public function testSelectableSource(): void
    {
        $this->driverTests($this->prepareSelectableDataSource());
    }

    public function testArraySource(): void
    {
        $this->driverTests($this->prepareArrayDataSource());
    }

    /**
     * @param DataSourceInterface<News> $dataSource
     */
    private function driverTests(DataSourceInterface $dataSource): void
    {
        $dataSource
            ->addField('title', 'text', ['comparison' => 'contains'])
            ->addField('author', 'text', ['comparison' => 'contains'])
            ->addField('created', 'datetime', ['comparison' => 'between', 'field' => 'createDate'])
        ;

        $result1 = $dataSource->getResult();
        self::assertCount(100, $result1);
        $dataSource->createView();

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

        //Checking cache.
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
                    'title' => 'title3',
                    'created' => [
                        'from' => new DateTimeImmutable(date('Y:m:d H:i:s', 35 * 24 * 60 * 60)),
                    ],
                ],
            ],
        ];
        $dataSource->bindParameters($parameters);
        $dataSource->createView();
        $result = $dataSource->getResult();
        self::assertCount(2, $result);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'author' => 'author3@domain2.com',
                ],
            ]
        ];
        $dataSource->bindParameters($parameters);
        $dataSource->createView();
        $result = $dataSource->getResult();
        self::assertCount(1, $result);

        // Checking sorting.
        $parameters = [
            $dataSource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'title' => 'desc'
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $result = $dataSource->getResult();
        self::assertInstanceOf(CollectionResult::class, $result);
        self::assertEquals('title99', $result->getIterator()->current()->getTitle());

        // Checking sorting.
        $parameters = [
            $dataSource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'author' => 'asc',
                    'title' => 'desc',
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $result = $dataSource->getResult();
        self::assertInstanceOf(CollectionResult::class, $result);
        self::assertEquals('author99@domain2.com', $result->getIterator()->current()->getAuthor());

        //Test for clearing fields.
        $dataSource->clearFields();
        $dataSource->setMaxResults(null);
        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'author' => 'domain1.com',
                ],
            ],
        ];

        // Since there are no fields now, we should have all of entities.
        $dataSource->bindParameters($parameters);
        $result = $dataSource->getResult();
        self::assertCount(100, $result);

        // Test boolean field
        $dataSource
            ->addField('active', 'boolean', ['comparison' => 'eq'])
        ;
        $dataSource->setMaxResults(null);
        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => 1,
                ],
            ]
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->createView();
        $result = $dataSource->getResult();
        self::assertCount(50, $result);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => 0,
                ],
            ]
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->createView();
        $result = $dataSource->getResult();
        self::assertCount(50, $result);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => true,
                ],
            ]
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->createView();
        $result = $dataSource->getResult();
        self::assertCount(50, $result);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => false,
                ],
            ]
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->createView();
        $result = $dataSource->getResult();
        self::assertCount(50, $result);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => null,
                ],
            ]
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->createView();
        $result = $dataSource->getResult();
        self::assertCount(100, $result);

        $parameters = [
            $dataSource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'active' => 'desc'
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $result = $dataSource->getResult();
        self::assertInstanceOf(CollectionResult::class, $result);
        self::assertFalse($result->getIterator()->current()->isActive());

        $parameters = [
            $dataSource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'active' => 'asc'
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $result = $dataSource->getResult();
        self::assertInstanceOf(CollectionResult::class, $result);
        self::assertFalse($result->getIterator()->current()->isActive());

        // test 'notIn' comparison
        $dataSource->addField('title_is_not', 'text', [
            'comparison' => 'notIn',
            'field' => 'title',
        ]);

        $parameters = [
            $dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title_is_not' => ['title1', 'title2', 'title3']
                ],
            ],
        ];

        $dataSource->bindParameters($parameters);
        $dataSource->createView();
        $result = $dataSource->getResult();
        self::assertCount(97, $result);
    }

    protected function setUp(): void
    {
        $config = ORMSetup::createConfiguration(true);
        $config->setMetadataDriverImpl(
            new XmlDriver(
                new SymfonyFileLocator(
                    [__DIR__ . '/../../Fixtures/doctrine' => 'Tests\FSi\Component\DataSource\Fixtures\Entity'],
                    '.orm.xml'
                )
            )
        );
        $em = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            $config
        );
        $tool = new SchemaTool($em);
        $classes = [
            $em->getClassMetadata(News::class),
            $em->getClassMetadata(Category::class),
            $em->getClassMetadata(Group::class),
        ];
        $tool->createSchema($classes);
        $this->load($em);
        $this->em = $em;
    }

    protected function tearDown(): void
    {
        unset($this->em);
    }

    /**
     * @return CollectionFactory<News>
     */
    private function getCollectionFactory(): CollectionFactory
    {
        return new CollectionFactory(
            $this->getEventDispatcher(),
            [
                new Boolean([]),
                new Date([]),
                new DateTime([]),
                new Number([]),
                new Text([]),
                new Time([]),
            ]
        );
    }

    private function getDataSourceFactory(): DataSourceFactory
    {
        $driverFactoryManager = new DriverFactoryManager([
            $this->getCollectionFactory()
        ]);

        return new DataSourceFactory($this->getEventDispatcher(), $driverFactoryManager);
    }

    /**
     * @return DataSourceInterface<News>
     */
    private function prepareSelectableDataSource(): DataSourceInterface
    {
        $driverOptions = [
            'collection' => $this->em->getRepository(News::class),
            'criteria' => Criteria::create()->orderBy(['title' => Criteria::ASC]),
        ];

        return $this->getDataSourceFactory()->createDataSource('collection', $driverOptions, 'datasource1');
    }

    /**
     * @return DataSourceInterface<News>
     */
    private function prepareArrayDataSource(): DataSourceInterface
    {
        $driverOptions = [
            'collection' => $this->em
                ->createQueryBuilder()
                ->select('n')
                ->from(News::class, 'n')
                ->indexBy('n', 'n.id')
                ->getQuery()
                ->execute(),
            'criteria' => Criteria::create()->orderBy(['title' => Criteria::ASC]),
        ];

        return $this->getDataSourceFactory()->createDataSource('collection', $driverOptions, 'datasource2');
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
            $this->eventDispatcher->addListener(
                PreGetResult::class,
                new Extension\Ordering\EventSubscriber\CollectionPreGetResult($this->getOrderingStorage())
            );
            $this->eventDispatcher->addListener(
                PreBindParameters::class,
                new Extension\Ordering\EventSubscriber\OrderingPreBindParameters($this->getOrderingStorage())
            );
            $this->eventDispatcher->addListener(
                PostGetParameters::class,
                new Extension\Ordering\EventSubscriber\OrderingPostGetParameters($this->getOrderingStorage())
            );
        }

        return $this->eventDispatcher;
    }

    private function getOrderingStorage(): Extension\Ordering\Storage
    {
        if (null === $this->orderingStorage) {
            $this->orderingStorage = new Extension\Ordering\Storage();
        }

        return $this->orderingStorage;
    }

    private function load(EntityManagerInterface $em): void
    {
        //Injects 5 categories.
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
            if ($i % 2 === 0) {
                $news->setAuthor('author' . $i . '@domain1.com');
                $news->setShortContent('Lorem ipsum.');
                $news->setContent('Content lorem ipsum.');
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
