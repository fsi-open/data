<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\HttpFoundation;

use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;

final class RequestCompatibilityHelper
{
    public static function get(ParameterBag $bag, string $name): array
    {
        if (true === class_exists(InputBag::class) && true === $bag instanceof InputBag) {
            return $bag->all($name);
        }

        return $bag->get($name, []);
    }
}
