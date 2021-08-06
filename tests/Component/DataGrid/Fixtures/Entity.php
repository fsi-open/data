<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Fixtures;

class Entity
{
    private string $name;
    private ?object $author = null;
    private ?EntityCategory $category = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAuthor(object $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): ?object
    {
        return $this->author;
    }

    public function getCategory(): ?EntityCategory
    {
        return $this->category;
    }

    public function setCategory(?EntityCategory $category): void
    {
        $this->category = $category;
    }
}
