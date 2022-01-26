<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataSource\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Category
{
    private ?int $id = null;
    private ?string $name;

    /**
     * @var Collection<int,News>
     */
    private Collection $news;

    public function __construct(?int $id = null)
    {
        $this->id = $id;
        $this->news = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    /**
     * @return array<News>
     */
    public function getNews(): array
    {
        return $this->news->toArray();
    }
}
