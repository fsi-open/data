<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Event;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;

abstract class ViewEventArgs extends DataSourceEventArgs
{
    private DataSourceViewInterface $view;

    /**
     * @param DataSourceInterface<mixed> $dataSource
     * @param DataSourceViewInterface $view
     */
    public function __construct(DataSourceInterface $dataSource, DataSourceViewInterface $view)
    {
        parent::__construct($dataSource);

        $this->view = $view;
    }

    public function getView(): DataSourceViewInterface
    {
        return $this->view;
    }
}
