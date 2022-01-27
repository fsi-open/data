<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Component\DataIndexer\Fixtures\Entity;

class Post
{
    protected string $idFirstPart;
    protected string $idSecondPart;

    public function __construct(string $idFirstPart, string $idSecondPart)
    {
        $this->idFirstPart = $idFirstPart;
        $this->idSecondPart = $idSecondPart;
    }

    public function getIdFirstPart(): string
    {
        return $this->idFirstPart;
    }

    public function getIdSecondPart(): string
    {
        return $this->idSecondPart;
    }
}
