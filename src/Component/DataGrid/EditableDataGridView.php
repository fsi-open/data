<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataGrid;

use FSi\Component\DataGrid\Data\DataRowsetInterface;
use InvalidArgumentException;

final class EditableDataGridView extends DataGridView
{
    private EditableDataGridFormHandlerInterface $formHandler;

    public function __construct(
        EditableDataGridFormHandlerInterface $formHandler,
        string $name,
        array $columns,
        DataRowsetInterface $rowset
    ) {
        parent::__construct($name, $columns, $rowset);

        $this->formHandler = $formHandler;
    }

    public function current(): DataGridRowViewInterface
    {
        $index = $this->rowset->key();

        return new EditableDataGridRowView($this->formHandler, $this->columns, $index, $this->rowset->current());
    }

    public function offsetGet($offset): DataGridRowViewInterface
    {
        if (isset($this->rowset[$offset])) {
            return new EditableDataGridRowView($this->formHandler, $this->columns, $offset, $this->rowset[$offset]);
        }

        throw new InvalidArgumentException(sprintf('Row "%s" does not exist in rowset.', $offset));
    }
}
