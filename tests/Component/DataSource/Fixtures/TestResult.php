<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Tests\Component\DataSource\Fixtures;

use ArrayIterator;
use FSi\Component\DataSource\Result;

class TestResult implements Result
{
    public function count(): int
    {
        return 0;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator([]);
    }
}
