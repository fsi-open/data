<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\Util\AttributesContainerInterface;

interface FieldViewInterface extends AttributesContainerInterface
{
    public function __construct(FieldInterface $field);
    public function getName(): string;
    public function getType(): string;
    /**
     * @return string|int|array<mixed>|null
     */
    public function getParameter();
    public function getDataSourceName(): string;
}
