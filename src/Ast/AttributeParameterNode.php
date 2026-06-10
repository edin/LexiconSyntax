<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    IdentifierNode::class,
    GrammarTokenType::Colon,
    AttributeTypeNode::class,
])]
final readonly class AttributeParameterNode
{
    public function __construct(
        public IdentifierNode $name,
        public Token $colon,
        public AttributeTypeNode $type
    ) {
    }
}
