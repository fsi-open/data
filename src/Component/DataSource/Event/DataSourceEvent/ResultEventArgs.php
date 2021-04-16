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
use FSi\Component\DataSource\Result;

class ResultEventArgs extends DataSourceEventArgs
{
    /**
     * @var Result
     */
    private $result;

    public function __construct(DataSourceInterface $datasource, Result $result)
    {
        parent::__construct($datasource);
        $this->setResult($result);
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    public function setResult(Result $result): void
    {
        $this->result = $result;
    }
}
