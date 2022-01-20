<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Util\AttributesContainer;

class FieldView extends AttributesContainer implements FieldViewInterface
{
    private string $dataSourceName;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $parameter;

    /**
     * {@inheritdoc}
     */
    public function __construct(FieldInterface $field)
    {
        $this->dataSourceName = $field->getDataSource()->getName();
        $this->name = $field->getName();
        $this->type = $field->getType()->getId();
        $this->parameter = $field->getParameter();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function getDataSourceName(): string
    {
        return $this->dataSourceName;
    }
}
