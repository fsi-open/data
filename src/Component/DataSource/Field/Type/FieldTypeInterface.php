<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Field\Type;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Field\FieldInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface FieldTypeInterface
{
    public function getId(): string;
    public function initOptions(OptionsResolver $optionsResolver): void;
    /**
     * @param DataSourceInterface $dataSource
     * @param string $name
     * @param array<string,mixed> $options
     * @return FieldInterface
     */
    public function createField(DataSourceInterface $dataSource, string $name, array $options): FieldInterface;
    public function createView(FieldInterface $field): FieldViewInterface;
}
