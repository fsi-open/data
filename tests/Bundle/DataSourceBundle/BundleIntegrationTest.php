<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use FOS\ElasticaBundle\Index\IndexManager;
use FOS\ElasticaBundle\Index\Resetter;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\TestKernel;
use Tests\FSi\Component\DataSource\Fixtures\Entity\Group;
use Tests\FSi\Component\DataSource\Fixtures\Entity\News;

final class BundleIntegrationTest extends WebTestCase
{
    /**
     * @var string
     */
    protected static $class = TestKernel::class;

    private KernelBrowser $client;
    private ?int $groupId;

    public function testDataSourceCollectionRendering(): void
    {
        $this->client->request('GET', '/test/collection');

        self::assertSelectorTextContains('.total', 'Total: 2');

        self::assertSelectorTextContains('tr:first-child td:first-child', 'A news');
        self::assertSelectorTextContains('tr:first-child td:last-child', 'An author');

        self::assertSelectorTextContains('tr:last-child td:first-child', 'Another news');
        self::assertSelectorTextContains('tr:last-child td:last-child', 'Another author');

        self::assertSelectorTextContains('label[for="news_fields_title"]', 'Title');
        self::assertSelectorExists('input[name="news[fields][title]"][type="text"]');

        self::assertSelectorTextContains('label[for="news_fields_active"]', 'Active');
        self::assertSelectorExists('select[name="news[fields][active]"]');

        self::assertSelectorTextContains('label[for="news_fields_createDate_from"]', 'Create date from');
        self::assertSelectorExists('input[name="news[fields][createDate][from]"][type="date"]');
        self::assertSelectorTextContains('label[for="news_fields_createDate_to"]', 'Create date to');
        self::assertSelectorExists('input[name="news[fields][createDate][to]"][type="date"]');

        self::assertSelectorTextContains('label[for="news_fields_createDateTime_from"]', 'Create date time from');
        self::assertSelectorExists('input[name="news[fields][createDateTime][from]"]');
        self::assertSelectorTextContains('label[for="news_fields_createDateTime_to"]', 'Create date time to');
        self::assertSelectorExists('input[name="news[fields][createDateTime][to]"]');

        self::assertSelectorExists('select[name="news[fields][createTime][hour]"]');
        self::assertSelectorExists('select[name="news[fields][createTime][minute]"]');

        self::assertSelectorTextContains('label[for="news_fields_views"]', 'Views');
        self::assertSelectorExists('input[name="news[fields][views]"][type="number"]');

        $this->client->submitForm('Filter', [
            'news[fields][title]' => 'A news',
            'news[fields][active]' => 0,
            'news[fields][createDate][from]' => '2021-01-01',
            'news[fields][createDateTime][from]' => '2021-01-01 15:00',
            'news[fields][createTime][hour]' => '16',
            'news[fields][createTime][minute]' => '00',
            'news[fields][views]' => 10
        ], 'GET');

        self::assertSelectorTextContains('tr:first-child td:first-child', 'A news');
        self::assertSelectorTextContains('tr:first-child td:last-child', 'An author');
        self::assertSelectorTextNotContains('tr:last-child td:first-child', 'Another news');
        self::assertSelectorTextNotContains('tr:last-child td:last-child', 'Another author');

        /** @var string $responseContent */
        $responseContent = $this->client->getResponse()->getContent();
        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/Resources/views/expected/datasource_test_collection.html',
            $responseContent
        );
    }

    public function testDataSourceDoctrineORMRendering(): void
    {
        $this->client->request('GET', '/test/doctrine-orm');

        self::assertSelectorTextContains('.total', 'Total: 2');

        self::assertSelectorTextContains('tr:first-child td:first-child', 'A news');
        self::assertSelectorTextContains('tr:first-child td:last-child', 'An author');

        self::assertSelectorTextContains('tr:last-child td:first-child', 'Another news');
        self::assertSelectorTextContains('tr:last-child td:last-child', 'Another author');

        self::assertSelectorTextContains('label[for="news_fields_title"]', 'Title');
        self::assertSelectorExists('input[name="news[fields][title]"][type="text"]');

        self::assertSelectorTextContains('label[for="news_fields_active"]', 'Active');
        self::assertSelectorExists('select[name="news[fields][active]"]');

        self::assertSelectorTextContains('label[for="news_fields_createDate_from"]', 'Create date from');
        self::assertSelectorExists('input[name="news[fields][createDate][from]"][type="date"]');
        self::assertSelectorTextContains('label[for="news_fields_createDate_to"]', 'Create date to');
        self::assertSelectorExists('input[name="news[fields][createDate][to]"][type="date"]');

        self::assertSelectorTextContains('label[for="news_fields_createDateTime_from"]', 'Create date time from');
        self::assertSelectorExists('input[name="news[fields][createDateTime][from]"]');
        self::assertSelectorTextContains('label[for="news_fields_createDateTime_to"]', 'Create date time to');
        self::assertSelectorExists('input[name="news[fields][createDateTime][to]"]');

        self::assertSelectorExists('select[name="news[fields][createTime][hour]"]');
        self::assertSelectorExists('select[name="news[fields][createTime][minute]"]');

        self::assertSelectorTextContains('label[for="news_fields_views"]', 'Views');
        self::assertSelectorExists('input[name="news[fields][views]"][type="number"]');

        self::assertSelectorTextContains('label[for="news_fields_groups"]', 'Group');
        self::assertSelectorExists('select[name="news[fields][groups]"]');

        $this->client->submitForm('Filter', [
            'news[fields][title]' => 'A news',
            'news[fields][active]' => 0,
            'news[fields][createDate][from]' => '2021-01-01',
            'news[fields][createDateTime][from]' => '2021-01-01 15:00:00',
            'news[fields][createTime][hour]' => '16',
            'news[fields][createTime][minute]' => '00',
            'news[fields][views]' => 10,
            'news[fields][groups]' => $this->groupId
        ], 'GET');

        self::assertSelectorTextContains('tr:first-child td:first-child', 'A news');
        self::assertSelectorTextContains('tr:first-child td:last-child', 'An author');
        self::assertSelectorTextNotContains('tr:last-child td:first-child', 'Another news');
        self::assertSelectorTextNotContains('tr:last-child td:last-child', 'Another author');
    }

    public function testDataSourceDoctrineDBALRendering(): void
    {
        $this->client->request('GET', '/test/doctrine-dbal');

        self::assertSelectorTextContains('.total', 'Total: 2');

        self::assertSelectorTextContains('tr:first-child td:first-child', 'A news');
        self::assertSelectorTextContains('tr:first-child td:last-child', 'An author');

        self::assertSelectorTextContains('tr:last-child td:first-child', 'Another news');
        self::assertSelectorTextContains('tr:last-child td:last-child', 'Another author');

        self::assertSelectorTextContains('label[for="news_fields_title"]', 'Title');
        self::assertSelectorExists('input[name="news[fields][title]"][type="text"]');

        self::assertSelectorTextContains('label[for="news_fields_active"]', 'Active');
        self::assertSelectorExists('select[name="news[fields][active]"]');

        self::assertSelectorTextContains('label[for="news_fields_createDate_from"]', 'Create date from');
        self::assertSelectorExists('input[name="news[fields][createDate][from]"][type="date"]');
        self::assertSelectorTextContains('label[for="news_fields_createDate_to"]', 'Create date to');
        self::assertSelectorExists('input[name="news[fields][createDate][to]"][type="date"]');

        self::assertSelectorTextContains('label[for="news_fields_createDateTime_from"]', 'Create date time from');
        self::assertSelectorExists('input[name="news[fields][createDateTime][from]"]');
        self::assertSelectorTextContains('label[for="news_fields_createDateTime_to"]', 'Create date time to');
        self::assertSelectorExists('input[name="news[fields][createDateTime][to]"]');

        self::assertSelectorExists('select[name="news[fields][createTime][hour]"]');
        self::assertSelectorExists('select[name="news[fields][createTime][minute]"]');

        self::assertSelectorTextContains('label[for="news_fields_views"]', 'Views');
        self::assertSelectorExists('input[name="news[fields][views]"][type="number"]');

        $this->client->submitForm('Filter', [
            'news[fields][title]' => 'A news',
            'news[fields][active]' => 0,
            'news[fields][createDate][from]' => '2021-01-01',
            'news[fields][createDateTime][from]' => '2021-01-01 15:00:00',
            'news[fields][createTime][hour]' => '16',
            'news[fields][createTime][minute]' => '00',
            'news[fields][views]' => 10
        ], 'GET');

        self::assertSelectorTextContains('tr:first-child td:first-child', 'A news');
        self::assertSelectorTextContains('tr:first-child td:last-child', 'An author');
        self::assertSelectorTextNotContains('tr:last-child td:first-child', 'Another news');
        self::assertSelectorTextNotContains('tr:last-child td:last-child', 'Another author');
    }

    public function testDataSourceElasticaRendering(): void
    {
        $this->client->request('GET', '/test/elastica');

        self::assertSelectorTextContains('.total', 'Total: 2');

        self::assertSelectorTextContains('tr:first-child td:first-child', 'A news');
        self::assertSelectorTextContains('tr:first-child td:last-child', 'An author');

        self::assertSelectorTextContains('tr:last-child td:first-child', 'Another news');
        self::assertSelectorTextContains('tr:last-child td:last-child', 'Another author');

        self::assertSelectorTextContains('label[for="news_fields_title"]', 'Title');
        self::assertSelectorExists('input[name="news[fields][title]"][type="text"]');

        self::assertSelectorTextContains('label[for="news_fields_active"]', 'Active');
        self::assertSelectorExists('select[name="news[fields][active]"]');

        self::assertSelectorTextContains('label[for="news_fields_createDate_from"]', 'Create date from');
        self::assertSelectorExists('input[name="news[fields][createDate][from]"][type="date"]');
        self::assertSelectorTextContains('label[for="news_fields_createDate_to"]', 'Create date to');
        self::assertSelectorExists('input[name="news[fields][createDate][to]"][type="date"]');

        self::assertSelectorTextContains('label[for="news_fields_createDateTime_from"]', 'Create date time from');
        self::assertSelectorExists('input[name="news[fields][createDateTime][from]"]');
        self::assertSelectorTextContains('label[for="news_fields_createDateTime_to"]', 'Create date time to');
        self::assertSelectorExists('input[name="news[fields][createDateTime][to]"]');

        self::assertSelectorExists('select[name="news[fields][createTime][hour]"]');
        self::assertSelectorExists('select[name="news[fields][createTime][minute]"]');

        self::assertSelectorTextContains('label[for="news_fields_views"]', 'Views');
        self::assertSelectorExists('input[name="news[fields][views]"][type="number"]');

        $this->client->submitForm('Filter', [
            'news[fields][title]' => 'A news',
            'news[fields][active]' => 0,
            'news[fields][createDate][from]' => '2021-01-01',
            'news[fields][createDateTime][from]' => '2021-01-01 15:00:00',
            'news[fields][views]' => 10,
            'news[fields][groups]' => $this->groupId
        ], 'GET');

        self::assertSelectorTextContains('tr:first-child td:first-child', 'A news');
        self::assertSelectorTextContains('tr:first-child td:last-child', 'An author');
        self::assertSelectorTextNotContains('tr:last-child td:first-child', 'Another news');
        self::assertSelectorTextNotContains('tr:last-child td:last-child', 'Another author');
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        $this->client = self::createClient(['debug' => false]);
        $this->setupDatabase();
    }

    private function setupDatabase(): void
    {
        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        /** @var Resetter $resetter */
        $resetter = $container->get('test.fos_elastica.resetter');
        $resetter->resetIndex('news');

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $tool = new SchemaTool($entityManager);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        $group = new Group();
        $group->setName('Breaking news');

        $otherGroup = new Group();
        $otherGroup->setName('International headlines');

        $news = new News();
        $news->setTitle('A news');
        $news->setAuthor('An author');
        $news->setCreateDate(new DateTimeImmutable('2021-01-01 15:00:00'));
        $news->setCreateTime(new DateTimeImmutable('1970-01-01 16:00:00'));
        $news->setActive(false);
        $news->setViews(10);
        $news->addGroup($group);

        $otherNews = new News();
        $otherNews->setTitle('Another news');
        $otherNews->setAuthor('Another author');
        $otherNews->setCreateDate(new DateTimeImmutable('2021-02-01 13:00:00'));
        $otherNews->setCreateTime(new DateTimeImmutable('1970-01-01 14:00:00'));
        $otherNews->setActive(true);
        $otherNews->setViews(5);
        $otherNews->addGroup($otherGroup);

        $entityManager->persist($group);
        $entityManager->persist($otherGroup);
        $entityManager->persist($news);
        $entityManager->persist($otherNews);
        $entityManager->flush();

        /** @var IndexManager $indexManager */
        $indexManager = $container->get('test.fos_elastica.index_manager');
        $indexManager->getIndex('news')->refresh();

        $this->groupId = $group->getId();
    }
}
