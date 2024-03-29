<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataGridBundle\Fixtures;

use Symfony\Component\HttpFoundation;

class Request extends HttpFoundation\Request
{
    public const ABSOLUTE_URI = 'http://example.com/?test=1&test=2';
    public const RELATIVE_URI = '/?test=1&test=2';

    public function __construct()
    {
    }

    public function getUri(): string
    {
        return self::ABSOLUTE_URI;
    }

    public function getRequestUri(): string
    {
        return self::RELATIVE_URI;
    }
}
