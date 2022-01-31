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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Throwable;

use function array_key_exists;
use function array_merge;
use function class_parents;
use function get_class;
use function is_array;
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
     * @param ManagerRegistry $registry
     * @param DataMapperInterface $dataMapper
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
            throw new InvalidArgumentException('Column "gedmo_tree" must read value from object.');
        }

        $value = parent::getValue($column, $object);

        $objectManager = $this->registry->getManager($column->getOption('em'));

        $treeListener = $this->getTreeListener($objectManager);

        $strategy = $this->getClassStrategy($objectManager, $treeListener, get_class($object));
        $this->validateStrategy($strategy);

        $config = $treeListener->getConfiguration($objectManager, get_class($object));
        $doctrineDataIndexer = new DoctrineDataIndexer($this->registry, get_class($object));
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $value = array_merge($value, [
            'id' => $doctrineDataIndexer->getIndex($object),
            'root' => isset($config['root']) ? $propertyAccessor->getValue($object, $config['root']) : null,
            'left' => isset($config['left']) ? $propertyAccessor->getValue($object, $config['left']) : null,
            'right' => isset($config['right']) ? $propertyAccessor->getValue($object, $config['right']) : null,
            'level' => isset($config['level']) ? $propertyAccessor->getValue($object, $config['level']) : null,
            'children' => $this->getTreeRepository(get_class($object), $objectManager)->childCount($object),
        ]);

        $parent = isset($config['parent']) ? $propertyAccessor->getValue($object, $config['parent']) : null;
        if (null !== $parent) {
            $value['parent'] = $doctrineDataIndexer->getIndex($parent);
        }

        return $value;
    }

    protected function initOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'em' => null,
        ]);
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

        $classParents = class_parents($class);
        if (false === is_array($classParents)) {
            throw new DataGridColumnException("Unable to determine parent classes of class {$class}");
        }

        $classParents = array_merge([$class], $classParents);
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
            foreach ($om->getEventManager()->getListeners() as $listeners) {
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
     * @param class-string $class
     * @param ObjectManager $em
     * @return TreeRepositoryInterface
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
            sprintf('Strategy "%s" is not supported by "%s" column.', $strategy->getName(), $this->getId())
        );
    }
}
