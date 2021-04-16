<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Util;

use function array_key_exists;

class AttributesContainer implements AttributesContainerInterface
{
    /**
     * @var array
     */
    protected $attributes = [];

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name)
    {
        if (false === $this->hasAttribute($name)) {
            return null;
        }

        return $this->attributes[$name];
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function removeAttribute($name): void
    {
        unset($this->attributes[$name]);
    }
}
