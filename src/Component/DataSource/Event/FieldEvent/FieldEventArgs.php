<?php

/**
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Event\FieldEvent;

use FSi\Component\DataSource\Field\FieldTypeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class FieldEventArgs extends Event
{
    /**
     * @var FieldTypeInterface
     */
    private $field;

    public function __construct(FieldTypeInterface $field)
    {
        $this->field = $field;
    }

    public function getField(): FieldTypeInterface
    {
        return $this->field;
    }
}
