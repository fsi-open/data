<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Fixtures;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class News
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $author = null;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private ?string $short_content = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $content = null;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?DateTimeImmutable $create_date = null;

    /**
     * @ORM\Column(type="time_immutable")
     */
    private ?DateTimeImmutable $create_time = null;

    /**
     * @ORM\ManyToOne(targetEntity="Tests\FSi\Component\DataSource\Fixtures\Category", inversedBy="news")
     */
    private ?Category $category = null;

    /**
     * @ORM\ManyToOne(targetEntity="Tests\FSi\Component\DataSource\Fixtures\Category")
     */
    private ?Category $category2 = null;

    /**
     * @ORM\ManyToMany(targetEntity="Tests\FSi\Component\DataSource\Fixtures\Group")
     * @var Collection<int,Group>
     */
    private Collection $groups;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $tags = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $active = false;

    public function __construct(int $id)
    {
        $this->id = $id;
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

    public function setShortContent(string $shortContent): void
    {
        $this->short_content = $shortContent;
    }

    public function getShortContent(): ?string
    {
        return $this->short_content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setCreateDate(DateTimeImmutable $createDate): void
    {
        $this->create_date = $createDate;
    }

    public function getCreateDate(): ?DateTimeImmutable
    {
        return $this->create_date;
    }

    public function setCreateTime(DateTimeImmutable $createTime): void
    {
        $this->create_time = $createTime;
    }

    public function getCreateTime(): ?DateTimeImmutable
    {
        return $this->create_time;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory2(Category $category2): void
    {
        $this->category2 = $category2;
    }

    public function getCategory2(): ?Category
    {
        return $this->category2;
    }

    /**
     * @return Collection<int,Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function setTags(string $tags): void
    {
        $this->tags = $tags;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setActive(bool $active = true): void
    {
        $this->active = $active;
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }
}
