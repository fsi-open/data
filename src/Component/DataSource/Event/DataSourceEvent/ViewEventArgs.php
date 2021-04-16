<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Event\DataSourceEvent;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;

class ViewEventArgs extends DataSourceEventArgs
{
    /**
     * @var DataSourceViewInterface
     */
    private $view;

    public function __construct(DataSourceInterface $datasource, DataSourceViewInterface $view)
    {
        parent::__construct($datasource);
        $this->view = $view;
    }

    public function getView(): DataSourceViewInterface
    {
        return $this->view;
    }
}
