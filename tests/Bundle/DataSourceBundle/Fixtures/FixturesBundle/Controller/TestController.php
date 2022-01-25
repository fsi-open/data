<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Fixtures\FixturesBundle\Controller;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FSi\Component\DataSource\DataSourceFactoryInterface;
use FSi\Component\DataSource\DataSourceInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\Entity\Group;
use Tests\FSi\Bundle\DataSourceBundle\Fixtures\Entity\News;
use Twig\Environment;

final class TestController
{
    private DataSourceFactoryInterface $dataSourceFactory;
    private Environment $twig;

    public function __construct(
        DataSourceFactoryInterface $DataSourceFactory,
        Environment $twig
    ) {
        $this->dataSourceFactory = $DataSourceFactory;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, string $driver): Response
    {
        $dataSource = $this->createDataSource($driver);
        $dataSource->setMaxResults(10);
        $dataSource->bindParameters($request->query->all());

        return new Response(
            $this->twig->render(
                '@Fixtures/test.html.twig',
                [
                    'datasource_view' => $dataSource->createView(),
                    'datasource_results' => $dataSource->getResult(),
                    'driver' => $driver
                ]
            )
        );
    }

    private function createDataSource(string $driver): DataSourceInterface
    {
        switch ($driver) {
            case 'collection':
                $parameters = ['collection' => $this->createNewsCollection()];
                break;
            case 'doctrine-dbal':
                $parameters = ['table' => 'news'];
                break;
            case 'doctrine-orm':
                $parameters = ['entity' => News::class];
                break;
            default:
                throw new RuntimeException("\"{$driver}\" is not supported");
        }

        $dataSource = $this->dataSourceFactory->createDataSource($driver, $parameters, 'news');
        if ('doctrine-orm' === $driver) {
            $dataSource->addField('groups', 'entity', [
                'comparison' => 'memberOf',
                'form_options' => [
                    'label' => 'Group',
                    'class' => Group::class
                ]
            ]);
        }

        $dataSource->addField('createDate', 'date', [
            'comparison' => 'between',
            'field' => 'doctrine-dbal' === $driver ? 'e.create_date' : 'createDate',
            'form_from_options' => [
                'label' => 'Create date from',
                'widget' => 'single_text',
                'input' => 'datetime_immutable'
            ],
            'form_to_options' => [
                'label' => 'Create date to',
                'widget' => 'single_text',
                'input' => 'datetime_immutable'
            ]
        ]);

        $dataSource->addField('createDateTime', 'datetime', [
            'comparison' => 'between',
            'field' => 'doctrine-dbal' === $driver ? 'e.create_date' : 'createDate',
            'form_from_options' => [
                'label' => 'Create date time from',
                'widget' => 'single_text',
                'input' => 'datetime_immutable'
            ],
            'form_to_options' => [
                'label' => 'Create date time to',
                'widget' => 'single_text',
                'input' => 'datetime_immutable'
            ]
        ]);

        $dataSource->addField('createTime', 'time', [
            'comparison' => 'eq',
            'field' => 'doctrine-dbal' === $driver ? 'e.create_time' : 'createTime',
            'form_options' => [
                'label' => 'Create time',
                'input' => 'datetime_immutable'
            ]
        ]);

        return $dataSource;
    }

    /**
     * @return Collection<int, News>
     */
    private function createNewsCollection(): Collection
    {
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

        return new ArrayCollection([$news, $otherNews]);
    }
}
