<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Gedmo\ColumnType;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use FSi\Component\DataGrid\DataGridFactory;
use FSi\Component\DataGrid\DataMapper\PropertyAccessorMapper;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\FSi\Component\DataGrid\Fixtures\EntityTree;
use FSi\Component\DataGrid\Extension\Gedmo\ColumnType\Tree;
use FSi\Component\DataGrid\Extension\Core\ColumnTypeExtension\DefaultColumnOptionsExtension;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use FSi\Component\DataGrid\DataGridInterface;
use Gedmo\Tree\RepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use Tests\FSi\Component\DataGrid\Fixtures\SimpleDataGridExtension;

class TreeTest extends TestCase
{
    public function testWrongValue(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $dataGridFactory = new DataGridFactory(
            [new SimpleDataGridExtension(new DefaultColumnOptionsExtension(), new Tree($registry))],
            $this->createMock(DataMapperInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );

        $dataGrid = $this->getDataGridMock();

        $column = $dataGridFactory->createColumn($dataGrid, Tree::class, 'tree', ['field_mapping' => ['id']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "gedmo_tree" must read value from object.');

        $dataGridFactory->createCellView($column, 1, ['key' => 'This is string, not object']);
    }

    public function testGetValue(): void
    {
        $registry = $this->getManagerRegistry();
        $dataGridFactory = new DataGridFactory(
            [new SimpleDataGridExtension(new DefaultColumnOptionsExtension(), new Tree($registry))],
            $this->createMock(DataMapperInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );

        $dataGrid = $this->getDataGridMock();

        $column = $dataGridFactory->createColumn($dataGrid, Tree::class, 'tree', ['field_mapping' => ['id']]);
        $view = $dataGridFactory->createCellView($column, 1, new EntityTree("foo"));

        self::assertSame(
            [
                "id" => "foo",
                "root" => "root",
                "left" => "left",
                "right" => "right",
                "level" => "level",
                "children" => 2,
                "parent" => "bar",
            ],
            $view->getValue()
        );
    }

    /**
     * @return ManagerRegistry&MockObject
     */
    protected function getManagerRegistry(): ManagerRegistry
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $managerRegistry
            ->method('getManagerForClass')
            ->willReturnCallback(
                function () {
                    $manager = $this->createMock(ObjectManager::class);
                    $manager
                        ->method('getMetadataFactory')
                        ->willReturnCallback(
                            function () {
                                $metadataFactory = $this->createMock(ClassMetadataFactory::class);

                                $metadataFactory
                                    ->method('getMetadataFor')
                                    ->willReturnCallback(
                                        function ($class) {
                                            if (EntityTree::class === $class) {
                                                $metadata = $this->createMock(ClassMetadataInfo::class);
                                                $metadata->method('getIdentifierFieldNames')->willReturn(['id']);

                                                return $metadata;
                                            }

                                            return null;
                                        }
                                    );

                                return $metadataFactory;
                            }
                        );

                    $manager
                        ->method('getClassMetadata')
                        ->willReturnCallback(
                            function ($class) {
                                if (EntityTree::class === $class) {
                                    /**
                                     * @var ClassMetadataInfo<EntityTree>&MockObject $metadata
                                     */
                                    $metadata = $this->createMock(ClassMetadataInfo::class);
                                    $metadata->method('getIdentifierFieldNames')->willReturn(['id']);
                                    $metadata->isMappedSuperclass = false;
                                    $metadata->rootEntityName = $class;

                                    return $metadata;
                                }

                                return null;
                            }
                        );

                    return $manager;
                }
            );

        $treeListener = $this->createMock(TreeListener::class);
        $strategy = $this->createMock(Strategy::class);

        $treeListener->expects(self::once())->method('getStrategy')->willReturn($strategy);

        $treeListener->method('getConfiguration')
            ->willReturn(
                [
                    'left' => 'left',
                    'right' => 'right',
                    'root' => 'root',
                    'level' => 'level',
                    'parent' => 'parent'
                ]
            );

        $strategy->method('getName')->willReturn('nested');

        $evm = $this->createMock(EventManager::class);
        $evm->method('getListeners')->willReturn([[$treeListener]]);

        $treeRepository = $this->createMock(RepositoryInterface::class);
        $treeRepository->method('childCount')->willReturn(2);

        $em = $this->createMock(EntityManager::class);
        $em->method('getEventManager')->willReturn($evm);
        $em->method('getRepository')->willReturn($treeRepository);

        $managerRegistry->method('getManager')->willReturn($em);

        return $managerRegistry;
    }

    /**
     * @return DataGridInterface&MockObject
     */
    private function getDataGridMock(): DataGridInterface
    {
        $dataGrid = $this->createMock(DataGridInterface::class);
        $dataGrid->method('getDataMapper')
            ->willReturn(new PropertyAccessorMapper(PropertyAccess::createPropertyAccessor()));

        return $dataGrid;
    }
}
