<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    GrammarTokenType::OpenBracket,
    [Part::SeparatedBy, AttributeValueNodeInterface::class, GrammarTokenType::Comma, true],
    GrammarTokenType::CloseBracket,
])]
final readonly class ArrayLiteralNode implements AttributeValueNodeInterface
{
    /**
     * @param list<AttributeValueNodeInterface> $items
     */
    public function __construct(
        public Token $open,
        public array $items,
        public Token $close
    ) {
    }
}
