<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\FSi\Component\DataIndexer\Fixtures;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Post
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    protected string $id_first_part;

    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    protected string $id_second_part;

    public function __construct(string $id_first_part, string $id_second_part)
    {
        $this->id_first_part = $id_first_part;
        $this->id_second_part = $id_second_part;
    }

    public function getIdFirstPart(): string
    {
        return $this->id_first_part;
    }

    public function getIdSecondPart(): string
    {
        return $this->id_second_part;
    }
}
