<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Extension\Gedmo\ColumnType\Tree;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
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

class TreeTypeTest extends TestCase
{
    public function testWrongValue(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $column = new Tree($registry);
        $column->setName('tree');
        $column->initOptions();

        $extension = new DefaultColumnOptionsExtension();
        $extension->initOptions($column);

        $object = ['This is string, not object'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "gedmo_tree" must read value from object.');
        $column->getValue($object);
    }

    public function testGetValue(): void
    {
        $dataGrid = $this->createMock(DataGridInterface::class);
        $registry = $this->getManagerRegistry();
        $dataMapper = $this->createMock(DataMapperInterface::class);

        $dataMapper->method('getData')->willReturn(new EntityTree('foo'));

        $column = new Tree($registry);
        $column->setName('tree');
        $column->initOptions();

        $extension = new DefaultColumnOptionsExtension();
        $extension->initOptions($column);

        $column->setDataMapper($dataMapper);
        $column->setOption('field_mapping', ['foo']);
        $column->setDataGrid($dataGrid);
        $object = new EntityTree("foo");

        $column->getValue($object);

        $view = $column->createCellView($object, '0');
        $column->buildCellView($view);

        self::assertSame(
            [
                "row" => "0",
                "id" => "foo",
                "root" => "root",
                "left" => "left",
                "right" => "right",
                "level" => "level",
                "children" => 2,
                "parent" => "bar",
            ],
            $view->getAttributes()
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
                                    /** @var ClassMetadataInfo&MockObject $metadata */
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
}
