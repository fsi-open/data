<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Elastica;

use Closure;
use Elastica\Client;
use Elastica\Document;
use Elastica\Mapping;
use FSi\Component\DataSource\DataSourceFactory as BaseDataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Driver\Elastica\ElasticaFactory;
use FSi\Component\DataSource\Driver\Elastica\Event\PreGetResult;
use FSi\Component\DataSource\Driver\Elastica\FieldType\Boolean;
use FSi\Component\DataSource\Driver\Elastica\FieldType\Date;
use FSi\Component\DataSource\Driver\Elastica\FieldType\DateTime;
use FSi\Component\DataSource\Driver\Elastica\FieldType\Entity;
use FSi\Component\DataSource\Driver\Elastica\FieldType\Number;
use FSi\Component\DataSource\Driver\Elastica\FieldType\Text;
use FSi\Component\DataSource\Driver\Elastica\FieldType\Time;
use FSi\Component\DataSource\Event\PostGetParameters;
use FSi\Component\DataSource\Event\PreBindParameters;
use FSi\Component\DataSource\Extension;
use FSi\Component\DataSource\Extension\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Extension\Ordering\Storage;
use FSi\Component\DataSource\Result;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function getenv;

abstract class TestBase extends TestCase
{
    private ?EventDispatcherInterface $eventDispatcher = null;
    private ?Storage $orderingStorage = null;
    /**
     * @var DataSourceInterface<array<string,mixed>>
     */
    protected DataSourceInterface $dataSource;

    protected function getDataSourceFactory(): BaseDataSourceFactory
    {
        $elasticaFactory = $this->getElasticaFactory();
        $driverFactoryManager = new DriverFactoryManager([$elasticaFactory]);

        return new BaseDataSourceFactory($this->getEventDispatcher(), $driverFactoryManager);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return Result<array<string, mixed>>
     */
    protected function filterDataSource(array $parameters): Result
    {
        $this->dataSource->bindParameters($this->parametersEnvelope($parameters));

        return $this->dataSource->getResult();
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    protected function parametersEnvelope(array $parameters): array
    {
        return [
            $this->dataSource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => $parameters,
            ],
        ];
    }

    /**
     * @param string $indexName
     * @param array<string, mixed> $mapping
     * @param Closure|null $transform
     * @return DataSourceInterface<array<string, mixed>>
     */
    protected function prepareIndex(
        string $indexName,
        array $mapping = [],
        ?Closure $transform = null
    ): DataSourceInterface {
        $client  = new Client(getenv('ELASTICSEARCH_URL') ?: []);
        $index = $client->getIndex($indexName);
        if ($index->exists()) {
            $index->delete();
        }
        $index->create();
        if (count($mapping) > 0) {
            $index->setMapping(new Mapping($mapping));
        }

        $documents = [];
        $fixtures = require(__DIR__ . '/Fixtures/documents.php');
        foreach ($fixtures as $id => $fixture) {
            if (null !== $transform) {
                $fixture = $transform($fixture);
            }
            $documents[] = new Document($id, $fixture);
        }
        $index->addDocuments($documents);
        $index->refresh();

        return $this->getDataSourceFactory()->createDataSource(
            'elastica',
            ['searchable' => $index]
        )->setMaxResults(100);
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
            $this->eventDispatcher->addListener(
                PreGetResult::class,
                new Extension\Ordering\EventSubscriber\ElasticaPreGetResult($this->getOrderingStorage())
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

    private function getOrderingStorage(): Storage
    {
        if (null === $this->orderingStorage) {
            $this->orderingStorage = new Storage();
        }

        return $this->orderingStorage;
    }

    /**
     * @return ElasticaFactory<array<string, mixed>>
     */
    protected function getElasticaFactory(): ElasticaFactory
    {
        $fieldExtensions = [new FieldExtension($this->getOrderingStorage())];

        return new ElasticaFactory(
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
}
