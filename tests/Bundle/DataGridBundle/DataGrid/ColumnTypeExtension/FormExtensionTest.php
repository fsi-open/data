<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\DataGrid\ColumnTypeExtension;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use FSi\Bundle\DataGridBundle\DataGrid\CellFormBuilder\EntityCellFormBuilder;
use FSi\Bundle\DataGridBundle\DataGrid\CellFormBuilder\TextCellFormBuilder;
use FSi\Bundle\DataGridBundle\DataGrid\ColumnTypeExtension\FormExtension;
use FSi\Component\DataGrid\Column\ColumnInterface;
use FSi\Component\DataGrid\Column\ColumnTypeInterface;
use FSi\Component\DataGrid\ColumnType\Entity as EntityColumntType;
use FSi\Component\DataGrid\ColumnType\Text;
use FSi\Component\DataGrid\DataGridInterface;
use FSi\Component\DataGrid\DataMapper\DataMapperInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ValidatorBuilder;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\Entity;
use Tests\FSi\Bundle\DataGridBundle\Fixtures\EntityCategory;

final class FormExtensionTest extends TestCase
{
    /**
     * @var DataGridInterface&MockObject
     */
    private MockObject $dataGrid;
    private FormExtension $extension;

    public function testSimpleSubmission(): void
    {
        $type = $this->createMock(Text::class);
        $type->method('getId')->willReturn('text');

        $column = $this->createColumnMock($type);
        $this->setColumnOptions($column, [
            'field_mapping' => ['name', 'author'],
            'editable' => true,
            'form_options' => [],
            'form_type' => [
                'name' => TextType::class,
                'author' => TextType::class,
            ]
        ]);

        $object = new Entity(1, 'old_name');
        $data = [
            'name' => 'object',
            'author' => 'norbert@fsi.pl',
            'invalid_data' => 'test'
        ];

        $this->extension->submit($column, 1, $object, $data);

        self::assertSame('norbert@fsi.pl', $object->getAuthor());
        self::assertSame('object', $object->getName());
    }

    public function testAvoidMappingDataBackWhenFormIsNotValid(): void
    {
        $type = $this->createMock(Text::class);
        $type->method('getId')->willReturn('text');

        $column = $this->createColumnMock($type);
        $this->setColumnOptions($column, [
            'field_mapping' => ['name', 'author'],
            'editable' => true,
            'form_options' => [
                'author' => [
                    'constraints' => [
                        new Email()
                    ]
                ]
            ],
            'form_type' => [
                'name' => TextType::class,
                'author' => TextType::class,
            ],
        ]);

        $object = new Entity(1, 'old_name');

        $data = [
            'name' => 'object',
            'author' => 'invalid_value',
        ];

        $this->extension->submit($column, 1, $object, $data);

        self::assertNull($object->getAuthor());
        self::assertSame('old_name', $object->getName());
    }

    public function testEntitySubmission(): void
    {
        $nestedEntityClass = EntityCategory::class;

        $type = $this->createMock(EntityColumntType::class);
        $type->method('getId')->willReturn('entity');

        $column = $this->createColumnMock($type);
        $this->setColumnOptions($column, [
            'editable' => true,
            'relation_field' => 'category',
            'field_mapping' => ['name'],
            'form_options' => [
                'category' => [
                    'class' => $nestedEntityClass,
                ]
            ],
            'form_type' => [],
        ]);

        $object = new Entity(1, 'name123');
        $data = [
            'category' => 1,
        ];

        self::assertNull($object->getCategory());

        $this->extension->submit($column, 1, $object, $data);

        self::assertInstanceOf($nestedEntityClass, $object->getCategory());
        self::assertSame('category name 1', $object->getCategory()->getName());
    }

    protected function setUp(): void
    {
        $entities = [
            new EntityCategory(1, 'category name 1'),
            new EntityCategory(2, 'category name 2'),
        ];

        $configuration = $this->createMock(Configuration::class);

        $objectManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $objectManager->method('getConfiguration')->willReturn($configuration);
        $objectManager->method('getExpressionBuilder')->willReturn(new Expr());

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->setConstructorArgs([$objectManager])
            ->onlyMethods(['execute', '_doExecute', 'getSql'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->getMock();
        $query->method('execute')->willReturn($entities);
        $query->method('setFirstResult')->willReturn($query);
        $query->method('setMaxResults')->willReturn($query);

        $objectManager->method('createQuery')->withAnyParameters()->willReturn($query);

        $queryBuilder = new QueryBuilder($objectManager);

        $entityClass = EntityCategory::class;
        $classMetadata = new ClassMetadata($entityClass);
        $classMetadata->identifier = ['id'];
        $classMetadata->fieldMappings = [
            'id' => [
                'type' => 'integer',
                'fieldName' => 'id',
                'columnName' => 'id',
                'inherited' => $entityClass,
                'options' => [],
            ]
        ];
        $classMetadata->reflFields = [
            'id' => new ReflectionProperty($entityClass, 'id'),
        ];

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->setConstructorArgs([$objectManager, $classMetadata])
            ->getMock();

        $repository->method('createQueryBuilder')->withAnyParameters()->willReturn($queryBuilder);
        $repository->method('findAll')->willReturn($entities);

        $objectManager->method('getClassMetadata')->withAnyParameters()->willReturn($classMetadata);
        $objectManager->method('getRepository')->willReturn($repository);
        $objectManager->method('contains')->willReturn(true);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($objectManager);
        $managerRegistry->method('getManagers')->willReturn([]);

        $validatorBuilder = new ValidatorBuilder();
        $resolvedTypeFactory = new ResolvedFormTypeFactory();
        $formRegistry = new FormRegistry(
            [
                new CoreExtension(),
                new DoctrineOrmExtension($managerRegistry),
                new CsrfExtension(new CsrfTokenManager()),
                new ValidatorExtension($validatorBuilder->getValidator())
            ],
            $resolvedTypeFactory
        );

        $formFactory = new FormFactory($formRegistry);

        /** @var DataGridInterface&MockObject $dataGrid */
        $dataGrid = $this->createMock(DataGridInterface::class);
        $this->dataGrid = $dataGrid;
        $this->dataGrid->method('getName')->willReturn('grid');
        $this->dataGrid->method('getDataMapper')->willReturn($this->getDataMapper());

        $this->extension = new FormExtension(
            [new TextCellFormBuilder(), new EntityCellFormBuilder()],
            $formFactory,
            true
        );
    }

    /**
     * @return ColumnInterface&MockObject
     */
    private function createColumnMock(ColumnTypeInterface $type): MockObject
    {
        /** @var ColumnInterface&MockObject $column */
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getDataGrid')->willReturn($this->dataGrid);
        $column->method('getType')->willReturn($type);

        return $column;
    }

    /**
     * @return DataMapperInterface&MockObject
     */
    private function getDataMapper(): MockObject
    {
        $dataMapper = $this->createMock(DataMapperInterface::class);
        $dataMapper->method('getData')
            ->willReturnCallback(
                static function ($field, $object) {
                    $method = 'get' . ucfirst($field);

                    return $object->$method();
                }
            );

        $dataMapper->method('setData')
            ->willReturnCallback(
                static function ($field, $object, $value) {
                    $method = 'set' . ucfirst($field);

                    return $object->$method($value);
                }
            );

        return $dataMapper;
    }

    /**
     * @param ColumnInterface&MockObject $column
     * @param array<string,mixed> $options
     */
    private function setColumnOptions(ColumnInterface $column, array $options): void
    {
        $column->method('getOption')
            ->willReturnCallback(
                static function ($option) use ($options) {
                    return $options[$option] ?? null;
                }
            );
    }
}
