<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Pagination;

/**
 * Pagination extension adds to view some options helpful during view rendering.
 */
class PaginationExtension
{
    /**
     * Key for page info.
     */
    public const PARAMETER_PAGE = 'page';
    /**
     * Key for results per page.
     */
    public const PARAMETER_MAX_RESULTS = 'max_results';
}
