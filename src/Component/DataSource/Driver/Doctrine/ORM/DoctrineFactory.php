<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Exception\DoctrineDriverException;
use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\QueryBuilder;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class DoctrineFactory implements DriverFactoryInterface
{
    /**
     * @var ManagerRegistry
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
     * @param ManagerRegistry $registry
     * @param array<DriverExtensionInterface> $extensions
     */
    public function __construct(ManagerRegistry $registry, array $extensions = [])
    {
        $this->registry = $registry;
        $this->extensions = $extensions;
        $this->optionsResolver = new OptionsResolver();
        $this->initOptions();
    }

    public function getDriverType(): string
    {
        return 'doctrine-orm';
    }

    public function createDriver(array $options = []): DriverInterface
    {
        $options = $this->optionsResolver->resolve($options);

        return new DoctrineDriver($this->extensions, $options['qb'], $options['useOutputWalkers']);
    }

    /**
     * @throws InvalidOptionsException
     */
    private function initOptions(): void
    {
        $this->optionsResolver->setDefaults([
            'entity' => null,
            'qb' => null,
            'alias' => 'e',
            'em' => null,
            'useOutputWalkers' => null,
        ]);

        $this->optionsResolver->setAllowedTypes('entity', ['string', 'null']);
        $this->optionsResolver->setAllowedTypes('qb', [QueryBuilder::class, 'null']);
        $this->optionsResolver->setAllowedTypes('alias', ['null', 'string']);
        $this->optionsResolver->setAllowedTypes('em', ['null', 'string', EntityManagerInterface::class]);
        $this->optionsResolver->setAllowedTypes('useOutputWalkers', ['null', 'bool']);

        $this->optionsResolver->setNormalizer('em', function (Options $options, $em): EntityManagerInterface {
            if (true === $em instanceof EntityManagerInterface) {
                return $em;
            }

            $objectManager = null;
            if (null === $em && null !== $options['entity']) {
                $objectManager = $this->registry->getManagerForClass($em);
            } elseif (null === $em || true === is_string($em)) {
                $objectManager = $this->registry->getManager($em);
            }
            if (null === $objectManager) {
                throw new DoctrineDriverException(
                    sprintf(
                        'Option "em" should contain an instance of %s or string but %s given',
                        EntityManagerInterface::class,
                        is_object($em) ? get_class($em) : gettype($em)
                    )
                );
            }

            if (true === $objectManager instanceof EntityManagerInterface) {
                return $objectManager;
            }

            throw new DoctrineDriverException(
                sprintf(
                    "Manager registry should return an instance of %s but returned instance of %s",
                    EntityManagerInterface::class,
                    get_class($objectManager)
                )
            );
        });
        $this->optionsResolver->setNormalizer(
            'qb',
            function (Options $options, ?QueryBuilder $queryBuilder): QueryBuilder {
                if (true === $queryBuilder instanceof QueryBuilder) {
                    return $queryBuilder;
                }

                if (null === $options['entity'] || false === $options['em'] instanceof EntityManagerInterface) {
                    throw new InvalidOptionsException('You must specify at least one option, "qb" or "entity".');
                }

                return $options['em']->createQueryBuilder()
                    ->select($options['alias'])
                    ->from($options['entity'], $options['alias'])
                ;
            }
        );
    }
}
