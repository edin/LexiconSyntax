<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    GrammarTokenType::OpenAngle,
    IdentifierNode::class,
    GrammarTokenType::CloseAngle,
])]
final readonly class CustomMatcherExpressionNode implements PrimaryExpressionNodeInterface
{
    public function __construct(
        public Token $open,
        public IdentifierNode $name,
        public Token $close
    ) {
    }
}
