<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use ArrayAccess;
use Countable;
use Iterator;

interface DataGridRowViewInterface extends Iterator, Countable, ArrayAccess
{
    /**
     * @return int|string
     */
    public function getIndex();

    /**
     * @return array|object
     */
    public function getSource();
}
