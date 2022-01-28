<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Event;

use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Result;

abstract class ResultEventArgs extends DriverEventArgs
{
    private Result $result;

    /**
     * @param DriverInterface $driver
     * @param array<FieldInterface> $fields
     * @param Result $result
     */
    public function __construct(DriverInterface $driver, array $fields, Result $result)
    {
        parent::__construct($driver, $fields);

        $this->setResult($result);
    }

    public function setResult(Result $result): void
    {
        $this->result = $result;
    }

    public function getResult(): Result
    {
        return $this->result;
    }
}
