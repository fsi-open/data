<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Data;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use FSi\Component\DataSource\Driver\Collection\CollectionResult;
use Tests\FSi\Component\DataGrid\Fixtures\Entity;
use FSi\Component\DataGrid\Data\DataRowset;
use PHPUnit\Framework\TestCase;
use TypeError;

class DataRowsetTest extends TestCase
{
    public function testCreateRowset(): void
    {
        $data = [
            'e1' => new Entity('entity1'),
            'e2' => new Entity('entity2')
        ];

        $rowset = new DataRowset($data);

        foreach ($rowset as $index => $row) {
            self::assertSame($data[$index] ?? null, $row);
        }

        self::assertSame(2, $rowset->count());
    }

    public function testCountableRowsetCreation(): void
    {
        $rawData = [
            'e1' => new Entity('entity1'),
            0 => new Entity('entity2'),
        ];
        $expectedData = [
            'e1' => $rawData['e1'],
        ];
        $data = new CollectionResult(
            new ArrayCollection($rawData),
            Criteria::create()->setMaxResults(1)
        );

        $rowset = new DataRowset($data);

        foreach ($rowset as $index => $row) {
            self::assertSame($expectedData[$index] ?? null, $row);
        }
        self::assertSame(2, $rowset->count());
    }
}
