<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Fixtures;

class EntityTree
{
    public string $id;
    public string $left = 'left';
    public string $right = 'right';
    public string $root = 'root';
    public string $level = 'level';
    public ?EntityTree $parent = null;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getParent(): ?self
    {
        if (null === $this->parent) {
            $this->parent = new EntityTree("bar");
        }

        return $this->parent;
    }
}
