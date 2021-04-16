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
use FSi\Component\DataSource\Util\AttributesContainerInterface;

/**
 * View of field, responsible for keeping some options needed during view rendering.
 */
interface FieldViewInterface extends AttributesContainerInterface
{
    public function __construct(FieldTypeInterface $field);

    public function getName(): string;

    public function getType(): string;

    public function getComparison(): string;

    public function getParameter(): string;

    public function setDataSourceView(DataSourceViewInterface $dataSourceView): void;

    public function getDataSourceView(): DataSourceViewInterface;
}
