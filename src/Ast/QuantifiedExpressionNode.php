<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;

final readonly class QuantifiedExpressionNode implements ExpressionNodeInterface
{
    public function __construct(
        public ExpressionNodeInterface $expression,
        public Token $quantifier
    ) {
    }
}
