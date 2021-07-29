<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid\Data;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * @template-extends Iterator<int|string,array|object>
 * @template-extends ArrayAccess<int|string,array|object>
 */
interface DataRowsetInterface extends Iterator, Countable, ArrayAccess
{
}
