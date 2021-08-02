<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\DataGrid\Extension\Symfony\ColumnTypeExtension;

use FSi\Component\DataGrid\Column\ColumnAbstractTypeExtension;
use FSi\Component\DataGrid\Extension\Core\ColumnType\Boolean;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_merge;
use function count;

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
        $yes = $this->translator->trans('datagrid.boolean.yes', [], 'DataGridBundle');
        $no = $this->translator->trans('datagrid.boolean.no', [], 'DataGridBundle');
        $optionsResolver->setDefaults([
            'true_value' => $yes,
            'false_value' => $no
        ]);

        $optionsResolver->setNormalizer(
            'form_options',
            function (Options $options, $value) use ($yes, $no) {
                if ($options['editable'] && 1 === count($options['field_mapping'])) {
                    $field = $options['field_mapping'][0];
                    $choices = [$no => 0, $yes => 1];

                    return array_merge(
                        [$field => ['choices' => $choices]],
                        $value
                    );
                }

                return $value;
            }
        );

        $optionsResolver->setNormalizer(
            'form_type',
            function (Options $options, $value) {
                if ($options['editable'] && 1 === count($options['field_mapping'])) {
                    $field = $options['field_mapping'][0];
                    return array_merge([$field => ChoiceType::class], $value);
                }

                return $value;
            }
        );
    }
}
