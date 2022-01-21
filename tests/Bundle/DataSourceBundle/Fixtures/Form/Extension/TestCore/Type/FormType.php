<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\FSi\Bundle\DataSourceBundle\Fixtures\Form\Extension\TestCore\Type;

use Symfony\Component\Form\Extension\Core\Type\FormType as BaseFormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class FormType extends BaseFormType
{
    /**
     * @param FormView $view
     * @param FormInterface<FormInterface> $form
     * @param array<string,mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['type'] = $form->getConfig()->getType()->getInnerType()->getBlockPrefix();
    }
}
