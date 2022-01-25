<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Fixtures\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class News
{
    private ?int $id = null;
    private ?string $title = null;
    private ?string $author = null;
    private ?string $shortContent = null;
    private ?string $content = null;
    private ?DateTimeImmutable $createDate = null;
    private ?DateTimeImmutable $createTime = null;
    private ?string $tags = null;
    private ?int $views = null;
    private bool $active = false;
    private ?Category $category = null;
    private ?Category $otherCategory = null;
    /**
     * @var Collection<int,Group>
     */
    private Collection $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setShortContent(?string $shortContent): void
    {
        $this->shortContent = $shortContent;
    }

    public function getShortContent(): ?string
    {
        return $this->shortContent;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setCreateDate(?DateTimeImmutable $createDate): void
    {
        $this->createDate = $createDate;
    }

    public function getCreateDate(): ?DateTimeImmutable
    {
        return $this->createDate;
    }

    public function setCreateTime(?DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getCreateTime(): ?DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setTags(string $tags): void
    {
        $this->tags = $tags;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(?int $views): void
    {
        $this->views = $views;
    }

    public function setActive(bool $active = true): void
    {
        $this->active = $active;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setOtherCategory(Category $otherCategory): void
    {
        $this->otherCategory = $otherCategory;
    }

    public function getOtherCategory(): ?Category
    {
        return $this->otherCategory;
    }

    /**
     * @return array<Group>
     */
    public function getGroups(): array
    {
        return $this->groups->toArray();
    }

    public function addGroup(Group $group): void
    {
        if (true === $this->groups->contains($group)) {
            return;
        }

        $this->groups->add($group);
    }
}
