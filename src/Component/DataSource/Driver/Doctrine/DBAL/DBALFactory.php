<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function get_class;
use function is_object;
use function is_string;
use function sprintf;

class DBALFactory implements DriverFactoryInterface
{
    /**
     * @var ConnectionRegistry
     */
    private $registry;

    /**
     * @var array<DriverExtensionInterface>
     */
    private $extensions;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @param ConnectionRegistry $registry
     * @param array<DriverExtensionInterface> $extensions
     */
    public function __construct(ConnectionRegistry $registry, array $extensions = [])
    {
        $this->registry = $registry;
        $this->extensions = $extensions;
        $this->optionsResolver = new OptionsResolver();
        $this->initOptions();
    }

    public function getDriverType(): string
    {
        return 'doctrine-dbal';
    }

    public function createDriver(array $options = []): DriverInterface
    {
        $options = $this->optionsResolver->resolve($options);

        return new DBALDriver($this->extensions, $options['qb'], $options['alias'], $options['indexField']);
    }

    private function initOptions(): void
    {
        $this->optionsResolver->setDefaults([
            'qb' => null,
            'table' => null,
            'alias' => 'e',
            'connection' => null,
            'indexField' => null,
        ]);

        $this->optionsResolver->setAllowedTypes('qb', [QueryBuilder::class, 'null']);
        $this->optionsResolver->setAllowedTypes('table', ['string', 'null']);
        $this->optionsResolver->setAllowedTypes('alias', ['null', 'string']);
        $this->optionsResolver->setAllowedTypes('connection', ['null', 'string', Connection::class]);
        $this->optionsResolver->setAllowedTypes('indexField', ['null', 'string', Closure::class]);

        $this->optionsResolver->setNormalizer('connection', function (Options $options, $connection): Connection {
            if (true === $connection instanceof Connection) {
                return $connection;
            }

            if (null === $connection || true === is_string($connection)) {
                $connection = $this->registry->getConnection($connection);

                if (false === $connection instanceof Connection) {
                    throw new DBALDriverException(
                        sprintf(
                            "Connection registry should return an instance of %s but returned instance of %s",
                            Connection::class,
                            get_class($connection)
                        )
                    );
                }

                return $connection;
            }

            throw new DBALDriverException(
                sprintf(
                    "Option \"connection\" should contain an instance of %s or a string but %s given",
                    Connection::class,
                    is_object($connection) ? get_class($connection) : gettype($connection)
                )
            );
        });
        $this->optionsResolver->setNormalizer(
            'qb',
            function (Options $options, ?QueryBuilder $queryBuilder): QueryBuilder {
                if (true === $queryBuilder instanceof QueryBuilder) {
                    return $queryBuilder;
                }

                if (null === $options['table'] || false === $options['connection'] instanceof Connection) {
                    throw new InvalidOptionsException('You must specify at least one option, "qb" or "table".');
                }

                return $options['connection']->createQueryBuilder()
                    ->select(sprintf('%s.*', $options['alias']))
                    ->from($options['table'], $options['alias'])
                ;
            }
        );
    }
}
