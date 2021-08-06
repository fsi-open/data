<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bundle\DataGridBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\TestKernel;

final class BundleIntegrationTest extends WebTestCase
{
    /**
     * @var string
     */
    protected static $class = TestKernel::class;

    public function testDataGridRenderingAndEditing(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test');
        $client->getResponse()->getContent();

        self::assertSelectorTextContains('thead th:nth-child(1) span', 'ID');
        self::assertSelectorTextContains('thead th:nth-child(2) span', 'Name');
        self::assertSelectorTextContains('thead th:nth-child(3) span', 'Author');
        self::assertSelectorTextContains('thead th:nth-child(4) span', 'Category');
        self::assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(1)', '1');
        self::assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(2)', 'Test 1');
        self::assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(3)', 'Author 1');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(1)', '2');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(2)', 'Test 2');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(3)', 'Author 2');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(4)', 'Category 2');

        $client->submitForm('Save', [
            'datagrid[0][name]' => 'new name 1',
            'datagrid[1][author]' => 'new author 2',
        ]);

        self::assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(2)', 'new name 1');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(3)', 'new author 2');
        self::assertInputValueSame('datagrid[0][name]', 'new name 1');
        self::assertInputValueSame('datagrid[1][author]', 'new author 2');
    }
}
