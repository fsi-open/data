<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Gedmo\ColumnType;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use FSi\Component\DataGrid\Column\ColumnAbstractType;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeExtensionInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\Exception\DataGridColumnException;
use FSi\Component\DataIndexer\DoctrineDataIndexer;
use Gedmo\Tree\RepositoryInterface as TreeRepositoryInterface;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

use function array_key_exists;
use function array_merge;
use function class_parents;
use function get_class;
use function is_object;
use function sprintf;

final class Tree extends ColumnAbstractType
{
    private ManagerRegistry $registry;
    /**
     * @var array<string>
     */
    private array $allowedStrategies;
    /**
     * @var array<class-string,Strategy>
     */
    private array $classStrategies;

    /**
     * @param array<ColumnTypeExtensionInterface> $columnTypeExtensions
     */
    public function __construct(
        ManagerRegistry $registry,
        DataMapperInterface $dataMapper,
        array $columnTypeExtensions
    ) {
        parent::__construct($columnTypeExtensions, $dataMapper);

        $this->registry = $registry;
        $this->allowedStrategies = ['nested'];
        $this->classStrategies = [];
    }

    public function getId(): string
    {
        return 'gedmo_tree';
    }

    public function getValue(ColumnInterface $column, $object)
    {
        if (false === is_object($object)) {
            throw new InvalidArgumentException(
                "Column \"{$this->getId()}\" must read value from object."
            );
        }

        $objectManager = $this->registry->getManager($column->getOption('em'));
        $treeListener = $this->getTreeListener($objectManager);

        $objectClass = get_class($object);
        $strategy = $this->getClassStrategy($objectManager, $treeListener, $objectClass);
        $this->validateStrategy($strategy);

        $config = $treeListener->getConfiguration($objectManager, $objectClass);
        $doctrineDataIndexer = new DoctrineDataIndexer($this->registry, $objectClass);

        $value = array_merge(
            parent::getValue($column, $object),
            [
                'id' => $doctrineDataIndexer->getIndex($object),
                'root' => $this->getPropertyFromConfig($object, $config, 'root'),
                'left' => $this->getPropertyFromConfig($object, $config, 'left'),
                'right' => $this->getPropertyFromConfig($object, $config, 'right'),
                'level' => $this->getPropertyFromConfig($object, $config, 'level'),
                'children' => $this->getTreeRepository($objectClass, $objectManager)->childCount($object)
            ]
        );

        $parent = $this->getPropertyFromConfig($object, $config, 'parent');
        if (null !== $parent) {
            $value['parent'] = $doctrineDataIndexer->getIndex($parent);
        }

        return $value;
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('em', null);
        $optionsResolver->setAllowedTypes('em', ['string', 'null']);
    }

    /**
     * @param ObjectManager $om
     * @param TreeListener $listener
     * @param class-string $class
     * @return Strategy
     */
    private function getClassStrategy(ObjectManager $om, TreeListener $listener, string $class): Strategy
    {
        if (true === array_key_exists($class, $this->classStrategies)) {
            return $this->classStrategies[$class];
        }

        $classParents = array_merge([$class], class_parents($class));
        foreach ($classParents as $parent) {
            try {
                $this->classStrategies[$class] = $listener->getStrategy($om, $parent);
                break;
            } catch (Throwable $e) {
                // we don't like to throw exception because there might be a strategy for class parents
            }
        }

        return $this->classStrategies[$class];
    }

    private function getTreeListener(ObjectManager $om): TreeListener
    {
        if (true === $om instanceof EntityManager) {
            foreach ($om->getEventManager()->getAllListeners() as $listeners) {
                $listeners = (array) $listeners;
                foreach ($listeners as $listener) {
                    if (true === $listener instanceof TreeListener) {
                        return $listener;
                    }
                }
            }
        }

        throw new DataGridColumnException('Gedmo TreeListener was not found in your entity manager.');
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return TreeRepositoryInterface<T>
     */
    private function getTreeRepository(string $class, ObjectManager $em): TreeRepositoryInterface
    {
        $repository = $em->getRepository($class);
        if (false === $repository instanceof TreeRepositoryInterface) {
            throw new RuntimeException(
                sprintf("%s must be an instance of Gedmo tree repository", get_class($repository))
            );
        }

        return $repository;
    }

    private function validateStrategy(Strategy $strategy): void
    {
        if (true === in_array($strategy->getName(), $this->allowedStrategies, true)) {
            return;
        }

        throw new DataGridColumnException(
            "Strategy \"{$strategy->getName()}\" is not supported by \"{$this->getId()}\" column."
        );
    }

    /**
     * @param array<string, mixed> $config
     * @return mixed
     */
    private function getPropertyFromConfig(object $object, array $config, string $key)
    {
        return array_key_exists($key, $config)
            ? $this->dataMapper->getData($config[$key], $object)
            : null
        ;
    }
}
