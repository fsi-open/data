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
    private $comparison;

    /**
     * @var string
     */
    private $parameter;

    /**
     * @var DataSourceViewInterface
     */
    private $dataSourceView;

    /**
     * {@inheritdoc}
     */
    public function __construct(FieldTypeInterface $field)
    {
        $this->name = $field->getName();
        $this->type = $field->getType();
        $this->comparison = $field->getComparison();
        $this->parameter = $field->getCleanParameter();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getComparison(): string
    {
        return $this->comparison;
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function setDataSourceView(DataSourceViewInterface $dataSourceView): void
    {
        $this->dataSourceView = $dataSourceView;
    }

    public function getDataSourceView(): DataSourceViewInterface
    {
        return $this->dataSourceView;
    }
}
