<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\EventSubscriber;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\FormStorage;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEventSubscriberInterface;
use FSi\Component\DataSource\Event\FieldEvent\PreBindParameter;

final class FieldPreBindParameter implements DataSourceEventSubscriberInterface
{
    private FormStorage $formStorage;

    public static function getPriority(): int
    {
        return 0;
    }

    public function __construct(FormStorage $formStorage)
    {
        $this->formStorage = $formStorage;
    }

    public function __invoke(PreBindParameter $event): void
    {
        $field = $event->getField();
        $form = $this->formStorage->getForm($field);
        if (null === $form) {
            return;
        }

        if ($form->isSubmitted()) {
            $form = $this->formStorage->getForm($field, true);
            if (null === $form) {
                return;
            }
        }

        $parameter = $event->getParameter();
        $fieldForm = $form->get(DataSourceInterface::PARAMETER_FIELDS)->get($field->getName());
        $fieldForm->submit($parameter);
        $this->formStorage->setParameter($field, $parameter);
        $event->setParameter($fieldForm->getData());
    }
}
