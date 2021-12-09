<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use FSi\Component\DataSource\Result;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @template-extends ArrayCollection<int|string,mixed>
 */
class DBALResult extends ArrayCollection implements Result
{
    private int $count;

    /**
     * @param Paginator $paginator
     * @param string|Closure $indexField
     */
    public function __construct(Paginator $paginator, $indexField)
    {
        if (false === is_string($indexField) && false === $indexField instanceof Closure) {
            throw new InvalidArgumentException(sprintf(
                'indexField should be string or %s but is %s',
                Closure::class,
                is_object($indexField) ? 'an instance of ' . get_class($indexField) : gettype($indexField)
            ));
        }

        $result = [];
        $this->count = $paginator->count();
        $data = $paginator->getIterator();

        $propertyAccessor = new PropertyAccessor();
        if (0 !== $data->count()) {
            foreach ($data as $element) {
                if (true === is_string($indexField)) {
                    $index = $propertyAccessor->getValue($element, $indexField);
                } else {
                    $index = $indexField($element);
                }

                if (null === $index) {
                    throw new RuntimeException('Index cannot be null');
                }

                if (true === array_key_exists($index, $result)) {
                    throw new RuntimeException("'Duplicate index \"{$index}\"'");
                }

                $result[$index] = $element;
            }
        }

        parent::__construct($result);
    }

    public function count(): int
    {
        return $this->count;
    }
}
