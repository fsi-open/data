<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\Fixtures\FixturesBundle\Controller;

use FSi\Component\DataGrid\DataGridFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\Entity;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\EntityCategory;
use Twig\Environment;

final class TestController
{
    private DataGridFactoryInterface $dataGridFactory;
    private Environment $twig;

    public function __construct(DataGridFactoryInterface $dataGridFactory, Environment $twig)
    {
        $this->dataGridFactory = $dataGridFactory;
        $this->twig = $twig;
    }

    public function __invoke(Request $request): Response
    {
        $dataGrid = $this->dataGridFactory->createDataGrid('datagrid');

        $category = new EntityCategory(1, 'Category 2');
        $dataGrid->setData([
            (new Entity(1, 'Test 1'))->setAuthor('Author 1'),
            (new Entity(2, 'Test 2'))->setAuthor('Author 2')->setCategory($category),
        ]);

        if ('POST' === $request->getMethod()) {
            $dataGrid->bindData($request);
        }

        return new Response($this->twig->render('@Fixtures/test.html.twig', ['datagrid' => $dataGrid->createView()]));
    }
}
