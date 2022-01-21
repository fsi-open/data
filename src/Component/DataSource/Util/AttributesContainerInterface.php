<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Util;

/**
 * Attributes container helps to handle attributes.
 */
interface AttributesContainerInterface
{
    public function hasAttribute(string $name): bool;
    /**
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute(string $name, $value): void;
    /**
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name);
    /**
     * @return array<string,mixed>
     */
    public function getAttributes(): array;
    public function removeAttribute(string $name): void;
}
