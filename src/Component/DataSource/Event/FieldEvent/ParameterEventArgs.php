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

class ParameterEventArgs extends FieldEventArgs
{
    /**
     * @var mixed
     */
    private $parameter;

    /**
     * @param FieldTypeInterface $field
     * @param mixed $parameter
     */
    public function __construct(FieldTypeInterface $field, $parameter)
    {
        parent::__construct($field);

        $this->setParameter($parameter);
    }

    /**
     * @param mixed $parameter
     */
    public function setParameter($parameter): void
    {
        $this->parameter = $parameter;
    }

    /**
     * @return mixed
     */
    public function getParameter()
    {
        return $this->parameter;
    }
}
