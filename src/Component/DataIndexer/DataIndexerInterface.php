<?php

/**
 * (c) FSi sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataIndexer;

interface DataIndexerInterface
{
    /**
     * @param array<string,mixed>|object $data
     * @return string
     */
    public function getIndex($data): string;

    /**
     * @param string $index
     * @return array<string,mixed>|object
     */
    public function getData(string $index);

    /**
     * @param array<int,int|string> $indexes
     * @return array<int|string,array<string,mixed>|object>
     */
    public function getDataSlice(array $indexes): array;
}
