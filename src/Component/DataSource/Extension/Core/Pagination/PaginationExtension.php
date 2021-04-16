<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Extension\Core\Pagination;

use FSi\Component\DataSource\DataSourceAbstractExtension;

/**
 * Pagination extension adds to view some options helpfull during view rendering.
 */
class PaginationExtension extends DataSourceAbstractExtension
{
    /**
     * Key for page info.
     */
    public const PARAMETER_PAGE = 'page';

    /**
     * Key for results per page.
     */
    public const PARAMETER_MAX_RESULTS = 'max_results';

    public function loadSubscribers(): array
    {
        return [
            new EventSubscriber\Events(),
        ];
    }
}
