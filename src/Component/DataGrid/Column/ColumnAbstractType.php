<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Column;

use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_reduce;
use function array_walk;
use function get_class;
use function is_a;
use function sprintf;

abstract class ColumnAbstractType implements ColumnTypeInterface
{
    use ColumnTypeTrait;

    /**
     * @param array<ColumnTypeExtensionInterface> $columnTypeExtensions
     */
    public function __construct(array $columnTypeExtensions)
    {
        array_walk($columnTypeExtensions, static function (ColumnTypeExtensionInterface $columnTypeExtension): void {
            $found = array_reduce(
                $columnTypeExtension::getExtendedColumnTypes(),
                static fn(bool $found, string $extendedColumnType): bool
                    => $found || true === is_a(static::class, $extendedColumnType, true),
                false
            );

            if (false === $found) {
                throw new DataGridColumnException(
                    sprintf(
                        'DataGrid column extension of class %s does not extend column type %s',
                        get_class($columnTypeExtension),
                        static::class
                    )
                );
            }
        });

        $this->columnTypeExtensions = $columnTypeExtensions;
    }

    public function createColumn(DataGridInterface $dataGrid, string $name, array $options): ColumnInterface
    {
        return new Column($dataGrid, $this, $name, $this->resolveOptions($name, $options));
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
    }

    protected function buildHeaderView(ColumnInterface $column, HeaderViewInterface $view): void
    {
    }

    protected function buildCellView(ColumnInterface $column, CellViewInterface $view): void
    {
    }
}
