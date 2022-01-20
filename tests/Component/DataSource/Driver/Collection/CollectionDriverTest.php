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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Collection\CollectionResult;
use FSi\Component\DataSource\Driver\Collection\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Collection\Exception\CollectionDriverException;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\Boolean;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\Date;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\DateTime;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\Number;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\Text;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\Time;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Event\DataSourceEvent\PostGetParameters;
use FSi\Component\DataSource\Event\DataSourceEvent\PreBindParameters;
use FSi\Component\DataSource\Extension\Core;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\FSi\Component\DataSource\Fixtures\Category;
use Tests\FSi\Component\DataSource\Fixtures\Group;
use Tests\FSi\Component\DataSource\Fixtures\News;
use PHPUnit\Framework\TestCase;

class CollectionDriverTest extends TestCase
{
    private EntityManager $em;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private ?Core\Ordering\Storage $orderingStorage = null;

    protected function setUp(): void
    {
        $dbParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/../../Fixtures'], true, null, null, false);
        $em = EntityManager::create($dbParams, $config);
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

    public function testComparingWithZero(): void
    {
        $datasource = $this->prepareArrayDataSource()->addField('id', 'number', ['comparison' => 'eq']);

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

    public function testSelectableSource(): void
    {
        $this->driverTests($this->prepareSelectableDataSource());
    }

    public function testArraySource(): void
    {
        $this->driverTests($this->prepareArrayDataSource());
    }

    private function driverTests(DataSourceInterface $datasource): void
    {
        $datasource
            ->addField('title', 'text', ['comparison' => 'contains'])
            ->addField('author', 'text', ['comparison' => 'contains'])
            ->addField('created', 'datetime', ['comparison' => 'between', 'field' => 'create_date'])
        ;

        $result1 = $datasource->getResult();
        self::assertCount(100, $result1);
        $datasource->createView();

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

        //Checking cache.
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
                    'title' => 'title3',
                    'created' => [
                        'from' => new DateTimeImmutable(date('Y:m:d H:i:s', 35 * 24 * 60 * 60)),
                    ],
                ],
            ],
        ];
        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        self::assertCount(2, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'author' => 'author3@domain2.com',
                ],
            ]
        ];
        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        self::assertCount(1, $result);

        // Checking sorting.
        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'title' => 'desc'
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        self::assertInstanceOf(CollectionResult::class, $result);
        self::assertEquals('title99', $result[0]->getTitle());

        // Checking sorting.
        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'author' => 'asc',
                    'title' => 'desc',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        self::assertInstanceOf(CollectionResult::class, $result);
        self::assertEquals('author99@domain2.com', $result[0]->getAuthor());

        //Test for clearing fields.
        $datasource->clearFields();
        $datasource->setMaxResults(null);
        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'author' => 'domain1.com',
                ],
            ],
        ];

        // Since there are no fields now, we should have all of entities.
        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        self::assertCount(100, $result);

        // Test boolean field
        $datasource
            ->addField('active', 'boolean', ['comparison' => 'eq'])
        ;
        $datasource->setMaxResults(null);
        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => 1,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        self::assertCount(50, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => 0,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        self::assertCount(50, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => true,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        self::assertCount(50, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => false,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        self::assertCount(50, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => null,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        self::assertCount(100, $result);

        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'active' => 'desc'
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        self::assertInstanceOf(CollectionResult::class, $result);
        self::assertFalse($result[0]->isActive());

        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'active' => 'asc'
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        self::assertInstanceOf(CollectionResult::class, $result);
        self::assertFalse($result[0]->isActive());

        // test 'notIn' comparison
        $datasource->addField('title_is_not', 'text', [
            'comparison' => 'notIn',
            'field' => 'title',
        ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title_is_not' => ['title1', 'title2', 'title3']
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        self::assertCount(97, $result);
    }

    protected function tearDown(): void
    {
        unset($this->em);
    }

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

    private function prepareSelectableDataSource(): DataSourceInterface
    {
        $driverOptions = [
            'collection' => $this->em->getRepository(News::class),
            'criteria' => Criteria::create()->orderBy(['title' => Criteria::ASC]),
        ];

        return $this->getDataSourceFactory()->createDataSource('collection', $driverOptions, 'datasource1');
    }

    private function prepareArrayDataSource(): DataSourceInterface
    {
        $driverOptions = [
            'collection' => $this->em
                ->createQueryBuilder()
                ->select('n')
                ->from(News::class, 'n')
                ->getQuery()
                ->execute(),
            'criteria' => Criteria::create()->orderBy(['title' => Criteria::ASC]),
        ];

        return $this->getDataSourceFactory()->createDataSource('collection', $driverOptions, 'datasource2');
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
            $this->eventDispatcher->addListener(
                PreGetResult::class,
                new Core\Ordering\EventSubscriber\CollectionPreGetResult($this->getOrderingStorage())
            );
            $this->eventDispatcher->addListener(
                PreBindParameters::class,
                new Core\Ordering\EventSubscriber\OrderingPreBindParameters($this->getOrderingStorage())
            );
            $this->eventDispatcher->addListener(
                PostGetParameters::class,
                new Core\Ordering\EventSubscriber\OrderingPostGetParameters($this->getOrderingStorage())
            );
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
