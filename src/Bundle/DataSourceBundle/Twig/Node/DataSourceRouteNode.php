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
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

final class DataSourceRouteNode extends Node
{
    /**
     * @param Node<Node> $dataGrid
     * @param Node<Node> $route
     * @param AbstractExpression<AbstractExpression> $additionalParameters
     * @param int $lineno
     * @param string|null $tag
     */
    public function __construct(
        Node $dataGrid,
        Node $route,
        AbstractExpression $additionalParameters,
        int $lineno,
        ?string $tag = null
    ) {
        parent::__construct(
            [
                'datasource' => $dataGrid,
                'route' => $route,
                'additional_parameters' => $additionalParameters
            ],
            [],
            $lineno,
            $tag
        );
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('$this->env->getRuntime(\'%s\')->setRoute(', DataSourceRuntime::class))
            ->subcompile($this->getNode('datasource'))
            ->raw(', ')
            ->subcompile($this->getNode('route'))
            ->raw(', ')
            ->subcompile($this->getNode('additional_parameters'))
            ->raw(");\n")
        ;
    }
}
