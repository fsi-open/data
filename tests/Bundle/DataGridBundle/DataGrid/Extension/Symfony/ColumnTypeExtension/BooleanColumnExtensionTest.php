<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension;

use FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension\FormExtension;
use FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension\BooleanColumnExtension;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanColumnExtensionTest extends TestCase
{
    public function testColumnOptions(): void
    {
        $optionsResolver = new OptionsResolver();
        $column = new Boolean();
        $column->initOptions($optionsResolver);
        $formExtension = new FormExtension($this->getFormFactory());
        $formExtension->initOptions($optionsResolver);
        $extension = new BooleanColumnExtension($this->getTranslator());
        $extension->initOptions($optionsResolver);
        $options = $optionsResolver->resolve();

        $this->assertEquals('YES', $options['true_value']);
        $this->assertEquals('NO', $options['false_value']);
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function getTranslator(): TranslatorInterface
    {
        /** @var TranslatorInterface&MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->expects(self::atLeast(2))
            ->method('trans')
            ->withConsecutive(
                ['datagrid.boolean.yes', [], 'DataGridBundle'],
                ['datagrid.boolean.no', [], 'DataGridBundle']
            )->willReturnOnConsecutiveCalls('YES', 'NO');

        return $translator;
    }

    /**
     * @return FormFactoryInterface&MockObject
     */
    private function getFormFactory(): FormFactoryInterface
    {
        /** @var FormFactoryInterface&MockObject $formFactory */
        $formFactory = $this->createMock(FormFactoryInterface::class);

        return $formFactory;
    }
}
