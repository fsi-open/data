<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Fixtures\Form\Extension\TestCore;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Field\FieldAbstractType;

final class TestFieldType extends FieldAbstractType
{
    private string $type;

    public function __construct(DataSourceInterface $dataSource, string $type, string $comparison)
    {
        parent::setDataSource($dataSource);

        $this->type = $type;
        $this->comparison = $comparison;
    }

    public function getName(): ?string
    {
        return 'name';
    }

    public function getType(): string
    {
        return $this->type;
    }
}
