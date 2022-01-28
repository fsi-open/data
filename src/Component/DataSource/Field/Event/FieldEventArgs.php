<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field\Event;

use FSi\Component\DataSource\Field\FieldInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class FieldEventArgs extends Event
{
    private FieldInterface $field;

    public function __construct(FieldInterface $field)
    {
        $this->field = $field;
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }
}
