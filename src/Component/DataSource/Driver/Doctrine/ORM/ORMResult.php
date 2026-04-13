<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use FSi\Component\DataIndexer\DoctrineDataIndexer;
use FSi\Component\DataSource\Result;

use function array_key_exists;
use function count;
use function get_class;
use function is_object;

/**
 * @template T
 * @template-implements Result<T>
 * @template-extends ArrayCollection<int|string, T>
 */
final class ORMResult extends ArrayCollection implements Result
{
    /**
     * @var int<0, max>
     */
    private int $count;

    /**
     * @param ManagerRegistry $registry
     * @param ORMPaginator<T> $paginator
     */
    public function __construct(ManagerRegistry $registry, ORMPaginator $paginator)
    {
        $this->count = $paginator->count();
        $data = $paginator->getIterator();

        $indexers = [];
        $result = [];
        if (0 !== $paginator->count()) {
            foreach ($data as $key => $element) {
                if (true === is_object($element)) {
                    $class = get_class($element);
                    if (false === array_key_exists($class, $indexers)) {
                        $indexers[$class] = new DoctrineDataIndexer($registry, $class);
                    }
                    $index = $indexers[$class]->getIndex($element);
                }

                $result[$index ?? $key] = $element;
            }
        }

        parent::__construct($result);
    }

    public function count(): int
    {
        return $this->count;
    }
}
