<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\DataSource\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use FSi\Bundle\DataSourceBundle\DataSource\EventSubscriber\FieldPreBindParameter;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\FormFieldExtension;
use FSi\Bundle\DataSourceBundle\DataSource\FormStorage;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Field\Event;
use FSi\Component\DataSource\Field\Field;
use FSi\Component\DataSource\Field\FieldView;
use FSi\Component\DataSource\Field\Type\EntityTypeInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\FSi\Component\DataSource\Fixtures\Entity\News;

final class FormExtensionEntityTest extends TestCase
{
    public function testEntityField(): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);
        $formStorage = new FormStorage($formFactory);
        $fieldExtension = new FormFieldExtension($formStorage, $translator);
        $fieldPreBindParameterSubscriber = new FieldPreBindParameter($formStorage);
        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('datasource');

        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'value']]];

        $fieldType = $this->createMock(EntityTypeInterface::class);
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired('name');
        $optionsResolver->setAllowedTypes('name', 'string');
        $optionsResolver->setDefault('name', 'name');
        $optionsResolver->setRequired('comparison');
        $optionsResolver->setAllowedTypes('comparison', 'string');
        $fieldExtension->initOptions($optionsResolver, $fieldType);
        $options = $optionsResolver->resolve(['comparison' => 'eq', 'form_options' => ['class' => News::class]]);
        $field = new Field($dataSource, $fieldType, 'name', $options);

        $event = new Event\PreBindParameter($field, $parameters);
        ($fieldPreBindParameterSubscriber)($event);
        // Form extension will remove 'name' => 'value' since this is not valid entity id
        // (since we have no entities at all).
        self::assertEquals(null, $event->getParameter());

        $fieldView = new FieldView($field);
        $fieldExtension->buildView($field, $fieldView);

        self::assertTrue($fieldView->hasAttribute('form'));
    }

    private function getFormFactory(): FormFactoryInterface
    {
        $entityManager = $this->getEntityManager();

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManager')->willReturn($entityManager);
        $managerRegistry->method('getManagerForClass')->willReturn($entityManager);

        $typeFactory = new ResolvedFormTypeFactory();
        $registry = new FormRegistry(
            [
                new CoreExtension(),
                new CsrfExtension(new CsrfTokenManager()),
                new DoctrineOrmExtension($managerRegistry),
            ],
            $typeFactory
        );

        return new FormFactory($registry);
    }

    private function getEntityManager(): EntityManager
    {
        $mappingRootDir = __DIR__ . '/../../../../Component/DataSource/Fixtures/doctrine';
        $entityNamespace = 'Tests\FSi\Component\DataSource\Fixtures\Entity';

        $config = Setup::createConfiguration(true, null, null);
        $config->setMetadataDriverImpl(
            new XmlDriver(
                new SymfonyFileLocator([$mappingRootDir => $entityNamespace], '.orm.xml')
            )
        );

        $em = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $tool = new SchemaTool($em);
        $tool->createSchema([$em->getClassMetadata(News::class)]);

        return $em;
    }
}
