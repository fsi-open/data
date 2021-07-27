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
use FSi\Component\DataGrid\Column\HeaderViewInterface;
use FSi\Component\DataGrid\DataGridRowViewInterface;
use Iterator;

/**
 * @template-extends Iterator<int|string,DataGridRowViewInterface>
 * @template-extends ArrayAccess<int|string,DataGridRowViewInterface>
 */
interface DataGridViewInterface extends Iterator, Countable, ArrayAccess
{
    public function getName(): string;

    /**
     * @return array<string,HeaderViewInterface>
     */
    public function getHeaders(): array;
}
