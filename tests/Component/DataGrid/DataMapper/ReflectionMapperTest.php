<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\DataMapper;

use FSi\Component\DataGrid\DataMapper\ReflectionMapper;
use Tests\FSi\Component\DataGrid\Fixtures\EntityMapper;
use FSi\Component\DataGrid\Exception\DataMappingException;
use PHPUnit\Framework\TestCase;

class ReflectionMapperTest extends TestCase
{
    public function testGetter(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();
        $entity->setName('fooname');

        self::assertSame('fooname', $mapper->getData('name', $entity));
    }

    public function testProtectedGetter(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();
        $entity->setSurname('foosurname');

        $this->expectException(DataMappingException::class);
        $this->expectExceptionMessage(
            sprintf('Method "getSurname()" is not public in class "%s"', EntityMapper::class)
        );
        $mapper->getData('surname', $entity);
    }

    public function testHasser(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();
        $entity->setCollection('collection');

        self::assertTrue($mapper->getData('collection', $entity));
    }

    public function testProtectedHasser(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();
        $entity->setPrivateCollection('collection');

        $this->expectException(DataMappingException::class);
        $this->expectExceptionMessage(
            sprintf('Method "hasPrivateCollection()" is not public in class "%s"', EntityMapper::class)
        );
        $mapper->getData('private_collection', $entity);
    }

    public function testIsser(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();
        $entity->setReady(true);

        self::assertTrue($mapper->getData('ready', $entity));
    }

    public function testProtectedIsser(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();
        $entity->setProtectedReady(true);

        $this->expectException(DataMappingException::class);
        $this->expectExceptionMessage(
            sprintf('Method "isProtectedReady()" is not public in class "%s"', EntityMapper::class)
        );
        $mapper->getData('protected_ready', $entity);
    }

    public function testProperty(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();
        $entity->setId('bar');

        self::assertSame('bar', $mapper->getData('id', $entity));
    }

    public function testPrivateProperty(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();
        $entity->setPrivateId('bar');

        $this->expectException(DataMappingException::class);
        $this->expectExceptionMessage(
            sprintf('Property "private_id" is not public in class "%s"', EntityMapper::class)
        );
        $mapper->getData('private_id', $entity);
    }

    public function testSetter(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();

        $mapper->setData('name', $entity, 'fooname');
        self::assertSame('fooname', $entity->getName());
    }

    public function testProtectedSetter(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();

        $this->expectException(DataMappingException::class);
        $this->expectExceptionMessage(
            sprintf('Method "setProtectedName()" is not public in class "%s"', EntityMapper::class)
        );
        $mapper->setData('protected_name', $entity, 'fooname');
    }

    public function testAdder(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();

        $mapper->setData('tag', $entity, 'bar');
        self::assertSame(['bar'], $entity->getTags());
    }

    public function testProtectedAdder(): void
    {
        $mapper = new ReflectionMapper();
        $entity = new EntityMapper();

        $this->expectException(DataMappingException::class);
        $this->expectExceptionMessage(
            sprintf('Method "addProtectedTag()" is not public in class "%s"', EntityMapper::class)
        );
        $mapper->setData('protected_tag', $entity, 'bar');
    }
}
