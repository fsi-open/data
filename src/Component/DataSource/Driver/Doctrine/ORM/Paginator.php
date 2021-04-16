<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM;

use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use FSi\Component\DataSource\Result;

class Paginator extends DoctrinePaginator implements Result
{
    public function __construct($query)
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

        parent::__construct($query, $fetchJoinCollection);
    }
}
