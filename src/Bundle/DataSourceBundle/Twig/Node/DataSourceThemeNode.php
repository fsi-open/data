<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\Node;

use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceExtension;
use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceRuntime;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

class DataSourceThemeNode extends Node
{
    /**
     * @param Node<Node> $dataGrid
     * @param Node<Node> $theme
     * @param AbstractExpression<AbstractExpression> $vars
     * @param int $lineno
     * @param string|null $tag
     */
    public function __construct(Node $dataGrid, Node $theme, AbstractExpression $vars, int $lineno, ?string $tag = null)
    {
        parent::__construct(['datasource' => $dataGrid, 'theme' => $theme, 'vars' => $vars], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('$this->env->getRuntime(\'%s\')->setTheme(', DataSourceRuntime::class))
            ->subcompile($this->getNode('datasource'))
            ->raw(', ')
            ->subcompile($this->getNode('theme'))
            ->raw(', ')
            ->subcompile($this->getNode('vars'))
            ->raw(");\n")
        ;
    }
}
