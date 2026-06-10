<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    PrimaryExpressionNodeInterface::class,
    [Part::Optional, [
        GrammarTokenType::Question,
        GrammarTokenType::Star,
        GrammarTokenType::Plus,
    ]],
], factory: 'create')]
final readonly class PostfixExpressionNode
{
    public static function create(
        ExpressionNodeInterface $expression,
        ?Token $quantifier
    ): ExpressionNodeInterface {
        if ($quantifier === null) {
            return $expression;
        }

        return new QuantifiedExpressionNode($expression, $quantifier);
    }
}
