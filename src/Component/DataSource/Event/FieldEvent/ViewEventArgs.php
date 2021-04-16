<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Event\FieldEvent;

use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;

class ViewEventArgs extends FieldEventArgs
{
    /**
     * @var FieldViewInterface
     */
    private $view;

    public function __construct(FieldTypeInterface $field, FieldViewInterface $view)
    {
        parent::__construct($field);
        $this->view = $view;
    }

    public function getView(): FieldViewInterface
    {
        return $this->view;
    }
}
