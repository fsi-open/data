<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Doctrine\DBAL\Fixtures;

use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use FSi\Component\DataSource\Event\DriverEvents;

class DBALDriverExtension extends DriverAbstractExtension
{
    /**
     * @var array
     */
    private $calls = [];

    /**
     * @var QueryBuilder|null
     */
    private $queryBuilder;

    public static function getSubscribedEvents(): array
    {
        return [
            DriverEvents::PRE_GET_RESULT => ['preGetResult', 128],
            DriverEvents::POST_GET_RESULT => ['postGetResult', 128],
        ];
    }

    public function getExtendedDriverTypes(): array
    {
        return ['doctrine-dbal'];
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function resetCalls(): void
    {
        $this->calls = [];
    }

    public function __call(string $name, array $arguments): void
    {
        if ('preGetResult' === $name) {
            $event = array_shift($arguments);
            $this->queryBuilder = $event->getDriver()->getQueryBuilder();
        }

        $this->calls[] = $name;
    }

    public function loadSubscribers(): array
    {
        return [$this];
    }

    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }
}
