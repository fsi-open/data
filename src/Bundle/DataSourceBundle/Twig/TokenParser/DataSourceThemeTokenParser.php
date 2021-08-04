<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\TokenParser;

use FSi\Bundle\DataSourceBundle\Twig\Node\DataSourceThemeNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class DataSourceThemeTokenParser extends AbstractTokenParser
{
    /**
     * @param Token $token
     * @return DataSourceThemeNode<Node>
     */
    public function parse(Token $token): DataSourceThemeNode
    {
        $stream = $this->parser->getStream();
        $dataSource = $this->parser->getExpressionParser()->parseExpression();
        $theme = $this->parser->getExpressionParser()->parseExpression();
        $vars = new ArrayExpression([], $stream->getCurrent()->getLine());

        if (true === $this->parser->getStream()->test(Token::NAME_TYPE, 'with')) {
            $this->parser->getStream()->next();

            if (true === $this->parser->getStream()->test(Token::PUNCTUATION_TYPE)) {
                $vars = $this->parser->getExpressionParser()->parseExpression();
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new DataSourceThemeNode($dataSource, $theme, $vars, $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'datasource_theme';
    }
}
