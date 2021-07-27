<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\DataMapper;

use FSi\Component\DataGrid\Exception\DataMappingException;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class PropertyAccessorMapper implements DataMapperInterface
{
    private PropertyAccessor $propertyAccessor;

    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function getData(string $field, $object)
    {
        try {
            $data = $this->propertyAccessor->getValue($object, $field);
        } catch (RuntimeException $e) {
            throw new DataMappingException($e->getMessage());
        }

        return $data;
    }

    public function setData(string $field, $object, $value): void
    {
        try {
            $this->propertyAccessor->setValue($object, $field, $value);
        } catch (RuntimeException $e) {
            throw new DataMappingException($e->getMessage());
        }
    }
}
