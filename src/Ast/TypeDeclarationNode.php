<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    GrammarTokenType::TypeKeyword,
    IdentifierNode::class,
    GrammarTokenType::Equal,
    TypeExpressionNode::class,
    GrammarTokenType::Semicolon,
])]
final readonly class TypeDeclarationNode
{
    public function __construct(
        public Token $keyword,
        public IdentifierNode $name,
        public Token $equal,
        public TypeExpressionNode $value,
        public Token $semicolon
    ) {
    }
}
