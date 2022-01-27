<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\ColumnType\Boolean;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanColumnExtension extends ColumnAbstractTypeExtension
{
    private TranslatorInterface $translator;

    public static function getExtendedColumnTypes(): array
    {
        return [Boolean::class];
    }

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'true_value' => 'datagrid.boolean.yes',
            'false_value' => 'datagrid.boolean.no',
        ]);

        $optionsResolver->setNormalizer(
            'true_value',
            fn(Options $options, string $value): string
                => $this->translator->trans($value, [], $options['translation_domain'] ?? 'DataGridBundle')
        );
        $optionsResolver->setNormalizer(
            'false_value',
            fn(Options $options, string $value): string
                => $this->translator->trans($value, [], $options['translation_domain'] ?? 'DataGridBundle')
        );
    }
}
