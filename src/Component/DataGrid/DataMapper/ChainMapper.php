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
use InvalidArgumentException;

final class ChainMapper implements DataMapperInterface
{
    /**
     * @var array<DataMapperInterface>
     */
    private array $mappers;

    /**
     * @param array<DataMapperInterface> $mappers
     * @throws InvalidArgumentException
     */
    public function __construct(array $mappers)
    {
        if (0 === count($mappers)) {
            throw new InvalidArgumentException('There must be at least one mapper in chain.');
        }

        $this->mappers = [];
        foreach ($mappers as $mapper) {
            if (false === $mapper instanceof DataMapperInterface) {
                throw new InvalidArgumentException(
                    sprintf('Mapper needs to implement "%s"', DataMapperInterface::class)
                );
            }

            $this->mappers[] = $mapper;
        }
    }

    public function getData(string $field, $object)
    {
        $data = null;
        $dataFound = false;
        $lastMsg = null;

        foreach ($this->mappers as $mapper) {
            try {
                $data = $mapper->getData($field, $object);
            } catch (DataMappingException $e) {
                $data = null;
                $lastMsg = $e->getMessage();

                continue;
            }

            $dataFound = true;
            break;
        }

        if (false === $dataFound) {
            if (null === $lastMsg) {
                $lastMsg = "Cant find any data that fit \"{$field}\" field.";
            }

            throw new DataMappingException($lastMsg);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function setData(string $field, $object, $value): void
    {
        $dataChanged = false;
        $lastMsg = null;

        foreach ($this->mappers as $mapper) {
            try {
                $mapper->setData($field, $object, $value);
            } catch (DataMappingException $e) {
                $lastMsg = $e->getMessage();
                continue;
            }

            $dataChanged = true;
            break;
        }

        if (false === $dataChanged) {
            if (null === $lastMsg) {
                $lastMsg = "Cant find any data that fit \"{$field}\" field.";
            }

            throw new DataMappingException($lastMsg);
        }
    }
}
