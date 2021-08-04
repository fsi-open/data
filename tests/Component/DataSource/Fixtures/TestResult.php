<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Fixtures;

use ArrayIterator;
use FSi\Component\DataSource\Result;

class TestResult implements Result
{
    public function count(): int
    {
        return 0;
    }

    /**
     * @return ArrayIterator<int,mixed>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator([]);
    }
}
