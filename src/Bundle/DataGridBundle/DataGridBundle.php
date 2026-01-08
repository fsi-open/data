<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle;

use FSi\Bundle\DataGridBundle\DependencyInjection\Compiler\DataGridPass;
use FSi\Bundle\DataGridBundle\DependencyInjection\Compiler\FilesPass;
use FSi\Bundle\DataGridBundle\DependencyInjection\Compiler\GedmoDataGridPass;
use FSi\Bundle\DataGridBundle\DependencyInjection\FSIDataGridExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DataGridBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new GedmoDataGridPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 200);
        $container->addCompilerPass(new DataGridPass());
        $container->addCompilerPass(new FilesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 200);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (false === $this->extension instanceof FSIDataGridExtension) {
            $this->extension = new FSIDataGridExtension();
        }

        return $this->extension;
    }
}
