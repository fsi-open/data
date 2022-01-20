<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM\Event;

use Doctrine\ORM\QueryBuilder;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event\DriverEvent\DriverEventArgs;

class PreGetResult extends DriverEventArgs
{
    private QueryBuilder $queryBuilder;

    public function __construct(DriverInterface $driver, array $fields, QueryBuilder $queryBuilder)
    {
        parent::__construct($driver, $fields);

        $this->queryBuilder = $queryBuilder;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}
