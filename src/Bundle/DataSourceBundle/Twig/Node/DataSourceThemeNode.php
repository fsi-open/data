<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\Twig\Node;

use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceRuntime;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\BodyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

#[YieldReady]
final class DataSourceThemeNode extends BodyNode
{
    /**
     * @param Node<Node> $dataGrid
     * @param Node<Node> $theme
     * @param AbstractExpression<AbstractExpression> $vars
     * @param int $lineno
     */
    public function __construct(Node $dataGrid, Node $theme, AbstractExpression $vars, int $lineno)
    {
        parent::__construct(['datasource' => $dataGrid, 'theme' => $theme, 'vars' => $vars], [], $lineno);
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
