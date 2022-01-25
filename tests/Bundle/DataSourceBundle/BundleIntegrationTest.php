<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bundle\DataSourceBundle;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\Entity\Group;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\Entity\News;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\TestKernel;

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
        self::assertSelectorExists('input[name="news[fields][createDateTime][from]"][type="datetime-local"]');
        self::assertSelectorTextContains('label[for="news_fields_createDateTime_to"]', 'Create date time to');
        self::assertSelectorExists('input[name="news[fields][createDateTime][to]"][type="datetime-local"]');

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

    public function testDataSourceDoctrineORMRendering(): void
    {
        $this->setupDataBase();
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
        self::assertSelectorExists('input[name="news[fields][createDateTime][from]"][type="datetime-local"]');
        self::assertSelectorTextContains('label[for="news_fields_createDateTime_to"]', 'Create date time to');
        self::assertSelectorExists('input[name="news[fields][createDateTime][to]"][type="datetime-local"]');

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
        $this->setupDataBase();
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
        self::assertSelectorExists('input[name="news[fields][createDateTime][from]"][type="datetime-local"]');
        self::assertSelectorTextContains('label[for="news_fields_createDateTime_to"]', 'Create date time to');
        self::assertSelectorExists('input[name="news[fields][createDateTime][to]"][type="datetime-local"]');

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

    protected function setUp(): void
    {
        $this->client = self::createClient(['debug' => false]);
    }

    private function setupDataBase(): void
    {
        $application = new Application($this->client->getKernel());
        $application->setAutoExit(false);
        $application->find('doctrine:schema:drop')->run(
            new ArrayInput(['--force' => true, '--quiet' => true]),
            new NullOutput()
        );
        $application->find('doctrine:schema:create')->run(
            new ArrayInput(['--quiet' => true]),
            new NullOutput()
        );

        /** @var EntityManagerInterface $manager */
        $manager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $group = new Group();
        $group->setName('Breaking news');

        $otherGroup = new Group();
        $otherGroup->setName('International headlines');

        $news = new News();
        $news->setTitle('A news');
        $news->setAuthor('An author');
        $news->setCreateDate(new DateTimeImmutable('2021-01-01 15:00'));
        $news->setCreateTime(new DateTimeImmutable('1970-01-01 16:00'));
        $news->setActive(false);
        $news->setViews(10);
        $news->addGroup($group);

        $otherNews = new News();
        $otherNews->setTitle('Another news');
        $otherNews->setAuthor('Another author');
        $otherNews->setCreateDate(new DateTimeImmutable('2021-02-01 13:00'));
        $otherNews->setCreateTime(new DateTimeImmutable('1970-01-01 14:00'));
        $otherNews->setActive(true);
        $otherNews->setViews(5);
        $otherNews->addGroup($otherGroup);

        $manager->persist($group);
        $manager->persist($otherGroup);
        $manager->persist($news);
        $manager->persist($otherNews);
        $manager->flush();

        $this->groupId = $group->getId();
    }
}
