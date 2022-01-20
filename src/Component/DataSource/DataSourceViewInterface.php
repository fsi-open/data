<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource;

use ArrayAccess;
use Countable;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Util\AttributesContainerInterface;
use SeekableIterator;

/**
 * @template-extends ArrayAccess<string,FieldViewInterface>
 * @template-extends SeekableIterator<string,FieldViewInterface>
 */
interface DataSourceViewInterface extends AttributesContainerInterface, ArrayAccess, Countable, SeekableIterator
{
    public function getName(): string;

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function getParameters(): array;

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function getAllParameters(): array;

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function getOtherParameters(): array;
}
