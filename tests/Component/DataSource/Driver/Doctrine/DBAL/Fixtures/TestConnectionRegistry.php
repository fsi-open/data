<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Driver\Doctrine\DBAL\Fixtures;

use Doctrine\Persistence\ConnectionRegistry;
use InvalidArgumentException;

use function class_exists;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
if (class_exists(\Doctrine\DBAL\Connection::class)) {
    class TestConnectionRegistry implements ConnectionRegistry
    {
        private \Doctrine\DBAL\Connection $connection;

        public function __construct(\Doctrine\DBAL\Connection $connection)
        {
            $this->connection = $connection;
        }

        public function getDefaultConnectionName(): string
        {
            return 'test';
        }

        public function getConnection($name = null): ?\Doctrine\DBAL\Connection
        {
            if (null !== $name && $this->getDefaultConnectionName() !== $name) {
                throw new InvalidArgumentException('invalid connection');
            }

            return $this->connection;
        }

        public function getConnections(): array
        {
            return [$this->connection];
        }

        public function getConnectionNames(): array
        {
            return [$this->getDefaultConnectionName()];
        }
    }
} else {
    class TestConnectionRegistry implements ConnectionRegistry
    {
        private \Doctrine\DBAL\Driver\Connection $connection;

        public function __construct(\Doctrine\DBAL\Driver\Connection $connection)
        {
            $this->connection = $connection;
        }

        public function getDefaultConnectionName(): string
        {
            return 'test';
        }

        public function getConnection($name = null): ?\Doctrine\DBAL\Driver\Connection
        {
            if (null !== $name && $this->getDefaultConnectionName() !== $name) {
                throw new InvalidArgumentException('invalid connection');
            }

            return $this->connection;
        }

        public function getConnections(): array
        {
            return [$this->connection];
        }

        public function getConnectionNames(): array
        {
            return [$this->getDefaultConnectionName()];
        }
    }
}
