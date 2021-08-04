<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataGrid\Fixtures;

class EntityMapper
{
    public ?string $id = null;
    private ?string $private_id = null;
    private ?string $name = null;
    private ?string $surname = null;
    private ?string $collection = null;
    private ?string $private_collection = null;
    private bool $ready = false;
    private bool $protected_ready = false;
    /**
     * @var array<string>
     */
    private array $tags = [];

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    protected function setProtectedName(string $name): void
    {
        $this->name = $name;
    }

    public function getHiddenPrivateId(): ?string
    {
        return $this->private_id;
    }

    public function setPrivateId(string $id): void
    {
        $this->private_id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    protected function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function hasCollection(): bool
    {
        return null !== $this->collection;
    }

    public function setCollection(string $collection): void
    {
        $this->collection = $collection;
    }

    public function setPrivateCollection(string $collection): void
    {
        $this->private_collection = $collection;
    }

    protected function hasPrivateCollection(): bool
    {
        return null !== $this->private_collection;
    }

    public function setReady(bool $ready): void
    {
        $this->ready = $ready;
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function setProtectedReady(bool $ready): void
    {
        $this->protected_ready = $ready;
    }

    protected function isProtectedReady(): bool
    {
        return $this->protected_ready;
    }

    public function addTag(string $tag): void
    {
        $this->tags[] = $tag;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    protected function addProtectedTag(string $tag): void
    {
        $this->tags[] = $tag;
    }
}
