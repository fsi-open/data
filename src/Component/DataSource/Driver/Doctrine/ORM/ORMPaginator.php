<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM;

use ArrayIterator;
use Countable;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use FSi\Component\DataSource\Result;
use Iterator;

class ORMPaginator implements Countable, Result
{
    /**
     * @var DoctrinePaginator<mixed>
     */
    private DoctrinePaginator $paginator;

    public function __construct(QueryBuilder $query)
    {
        // Avoid DDC-2213 bug/mistake
        $em = $query->getEntityManager();
        $fetchJoinCollection = true;
        foreach ($query->getRootEntities() as $entity) {
            if ($em->getClassMetadata($entity)->isIdentifierComposite) {
                $fetchJoinCollection = false;
                break;
            }
        }

        $this->paginator = new DoctrinePaginator($query, $fetchJoinCollection);
    }

    /**
     * @return ArrayIterator<int|string,mixed>
     */
    public function getIterator(): Iterator
    {
        return $this->paginator->getIterator();
    }

    public function count(): int
    {
        return $this->paginator->count();
    }

    public function setUseOutputWalkers(?bool $useOutputWalkers): void
    {
        $this->paginator->setUseOutputWalkers($useOutputWalkers);
    }
}
