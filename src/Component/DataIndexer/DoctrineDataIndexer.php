<?php

/**
 * (c) FSi sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataIndexer;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\PropertyAccess\PropertyAccess;
use FSi\Component\DataIndexer\Exception\InvalidArgumentException;
use FSi\Component\DataIndexer\Exception\RuntimeException;

class DoctrineDataIndexer implements DataIndexerInterface
{
    private const SEPARATOR = "|";

    private ObjectManager $manager;

    /**
     * @var class-string
     */
    private string $class;

    /**
     * @param ManagerRegistry $registry
     * @param class-string $class
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function __construct(ManagerRegistry $registry, string $class)
    {
        $this->manager = $this->tryToGetObjectManager($registry, $class);
        $this->class = $this->tryToGetRootClass($class);
    }

    public function getIndex($data): string
    {
        $data = $this->validateData($data);

        return $this->joinIndexParts($this->getIndexParts($data));
    }

    /**
     * @param string $index
     * @return object
     */
    public function getData(string $index): object
    {
        return $this->tryToFindEntity($this->buildSearchCriteria($index));
    }

    /**
     * @param array<int,int|string> $indexes
     * @return array<int|string,object>
     */
    public function getDataSlice(array $indexes): array
    {
        return $this->getRepository()->findBy($this->buildMultipleSearchCriteria($indexes));
    }

    /**
     * @param array<string,mixed>|object $data
     * @return object
     */
    public function validateData($data): object
    {
        if (false === is_object($data)) {
            throw new InvalidArgumentException("DoctrineDataIndexer can index only objects.");
        }

        if (false === $data instanceof $this->class) {
            throw new InvalidArgumentException(sprintf(
                'DoctrineDataIndexer expects data as instance of "%s" instead of "%s".',
                $this->class,
                get_class($data)
            ));
        }

        return $data;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Returns an array of identifier field names for self::$class.
     *
     * @return array<int,string>
     */
    private function getIdentifierFieldNames(): array
    {
        return $this->manager
            ->getClassMetadata($this->class)
            ->getIdentifierFieldNames();
    }

    /**
     * @param ManagerRegistry $registry
     * @param class-string<object> $class
     * @return ObjectManager
     * @throws Exception\InvalidArgumentException
     */
    private function tryToGetObjectManager(ManagerRegistry $registry, string $class): ObjectManager
    {
        $manager = $registry->getManagerForClass($class);

        if (null === $manager) {
            throw new InvalidArgumentException(sprintf(
                'ManagerRegistry doesn\'t have manager for class "%s".',
                $class
            ));
        }

        return $manager;
    }

    /**
     * @param class-string $class
     * @return class-string
     */
    private function tryToGetRootClass(string $class): string
    {
        $classMetadata = $this->manager->getClassMetadata($class);

        if (false === $classMetadata instanceof ClassMetadataInfo) {
            throw new RuntimeException("Only Doctrine ORM is supported at the moment");
        }

        if (true === $classMetadata->isMappedSuperclass) {
            throw new RuntimeException('DoctrineDataIndexer can\'t be created for mapped super class.');
        }

        return $classMetadata->rootEntityName;
    }

    /**
     * @param object $object
     * @return array<int,string>
     */
    private function getIndexParts(object $object): array
    {
        $identifiers = $this->getIdentifierFieldNames();

        $accessor = PropertyAccess::createPropertyAccessor();
        return array_map(
            static function ($identifier) use ($object, $accessor) {
                return $accessor->getValue($object, $identifier);
            },
            $identifiers
        );
    }

    /**
     * @param array<int,string> $indexes
     * @return string
     */
    private function joinIndexParts(array $indexes): string
    {
        return implode(self::SEPARATOR, $indexes);
    }

    /**
     * @param string $index
     * @param int $identifiersCount
     * @return array<int,string>
     */
    private function splitIndex(string $index, int $identifiersCount): array
    {
        $indexParts = explode(self::SEPARATOR, $index);
        if (count($indexParts) !== $identifiersCount) {
            throw new RuntimeException(
                "Can't split index into parts. Maybe you should consider using different separator?"
            );
        }

        return $indexParts;
    }

    /**
     * @param array<int,int|string> $indexes
     * @return array<string,array<int,string>>
     */
    private function buildMultipleSearchCriteria(array $indexes): array
    {
        $multipleSearchCriteria = array();
        foreach ($indexes as $index) {
            foreach ($this->buildSearchCriteria((string) $index) as $identifier => $indexPart) {
                if (false === array_key_exists($identifier, $multipleSearchCriteria)) {
                    $multipleSearchCriteria[$identifier] = array();
                }

                $multipleSearchCriteria[$identifier][] = $indexPart;
            }
        }
        return $multipleSearchCriteria;
    }

    /**
     * @param string $index
     * @return array<string,string>
     */
    private function buildSearchCriteria(string $index): array
    {
        $identifiers = $this->getIdentifierFieldNames();
        $indexParts = $this->splitIndex($index, count($identifiers));

        $result = array_combine($identifiers, $indexParts);
        if (false === $result) {
            throw new RuntimeException(
                "Number of index parts in \"$index\" does not match identifier fields for \"$this->class\" entity"
            );
        }

        return $result;
    }

    /**
     * @param array<string,string> $searchCriteria
     * @return object
     */
    private function tryToFindEntity(array $searchCriteria): object
    {
        $entity = $this->getRepository()->findOneBy($searchCriteria);

        if (null === $entity) {
            throw new RuntimeException(
                'Can\'t find any entity using the following search criteria: "' . implode(", ", $searchCriteria) . '"'
            );
        }

        return $entity;
    }

    /**
     * @return ObjectRepository<object>
     */
    private function getRepository(): ObjectRepository
    {
        return $this->manager->getRepository($this->class);
    }
}
