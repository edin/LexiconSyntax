<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;

final readonly class ActionExpressionNode implements ExpressionNodeInterface
{
    public function __construct(
        public ExpressionNodeInterface $pattern,
        public Token $arrow,
        public ActionValueNodeInterface $action
    ) {
    }
}
