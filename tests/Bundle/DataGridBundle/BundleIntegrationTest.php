<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle;

use DOMAttr;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\TestKernel;

final class BundleIntegrationTest extends WebTestCase
{
    public function testDataGridRenderingAndEditing(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test');
        $client->getResponse()->getContent();
        $crawler = $client->getCrawler();

        self::assertSelectorTextContains('thead th:nth-child(1) span', 'ID');
        self::assertSelectorTextContains('thead th:nth-child(2) span', 'Name');
        self::assertSelectorTextContains('thead th:nth-child(3) span', 'Author');
        self::assertSelectorTextContains('thead th:nth-child(4) span', 'File');
        self::assertSelectorTextContains('thead th:nth-child(5) span', 'Image');
        self::assertSelectorTextContains('thead th:nth-child(6) span', 'Category');
        self::assertSelectorTextContains('thead th:nth-child(7) span', 'Actions');
        self::assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(1)', '1');
        self::assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(2)', 'Test 1');
        self::assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(3)', 'Author 1');
        $aNode = $crawler->filter('tbody tr:nth-child(1) td:nth-child(4) a')->getNode(0);
        $aNode = $crawler->filter('tbody tr:nth-child(1) td:nth-child(7) a')->getNode(0);
        self::assertNotNull($aNode);
        self::assertNotNull($aNode->attributes);
        $href = $aNode->attributes->getNamedItem('href') ?? new DOMAttr('href');
        self::assertInstanceOf(DOMAttr::class, $href);
        self::assertEquals('/test/1?redirect_uri=/test', $href->value);
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(1)', '2');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(2)', 'Test 2');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(3)', 'Author 2');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(6)', 'Category 2');
        $aNode = $crawler->filter('tbody tr:nth-child(2) td:nth-child(4) a')->getNode(0);
        $aNode = $crawler->filter('tbody tr:nth-child(2) td:nth-child(7) a')->getNode(0);
        self::assertNotNull($aNode);
        self::assertNotNull($aNode->attributes);
        $href = $aNode->attributes->getNamedItem('href') ?? new DOMAttr('href');
        self::assertInstanceOf(DOMAttr::class, $href);
        self::assertEquals('/test/2?redirect_uri=/test', $href->value);

        $client->submitForm('Save', [
            'datagrid[0][name]' => 'new name 1',
            'datagrid[1][author]' => 'new author 2',
        ]);

        self::assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(2)', 'new name 1');
        self::assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(3)', 'new author 2');
        self::assertInputValueSame('datagrid[0][name]', 'new name 1');
        self::assertInputValueSame('datagrid[1][author]', 'new author 2');
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
