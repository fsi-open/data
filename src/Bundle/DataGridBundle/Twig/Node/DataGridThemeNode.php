<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataGridBundle\Twig\Node;

use FSi\Bundle\DataGridBundle\Twig\Extension\DataGridRuntime;
use Twig\Compiler;
use Twig\Extension\AbstractExtension;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;

class DataGridThemeNode extends Node
{
    /**
     * @param Node<Node> $dataGrid
     * @param Node<Node> $theme
     * @param ArrayExpression<AbstractExtension> $vars
     * @param int $lineno
     * @param string|null $tag
     */
    public function __construct(
        Node $dataGrid,
        Node $theme,
        ArrayExpression $vars,
        int $lineno,
        ?string $tag = null
    ) {
        parent::__construct(['datagrid' => $dataGrid, 'theme' => $theme, 'vars' => $vars], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('$this->env->getRuntime(\'%s\')->setTheme(', DataGridRuntime::class))
            ->subcompile($this->getNode('datagrid'))
            ->raw(', ')
            ->subcompile($this->getNode('theme'))
            ->raw(', ')
            ->subcompile($this->getNode('vars'))
            ->raw(");\n");
    }
}
