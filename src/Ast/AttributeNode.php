<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    GrammarTokenType::AttributeStart,
    IdentifierNode::class,
    [Part::Optional, [Part::ListBetween, AttributeArgumentNode::class, GrammarTokenType::Comma, GrammarTokenType::OpenParen, GrammarTokenType::CloseParen, true]],
    GrammarTokenType::CloseBracket,
])]
final readonly class AttributeNode
{
    /** @var list<AttributeArgumentNode> */
    public array $arguments;

    /**
     * @param list<AttributeArgumentNode>|null $arguments
     */
    public function __construct(
        public Token $start,
        public IdentifierNode $name,
        ?array $arguments,
        public Token $end
    ) {
        $this->arguments = $arguments ?? [];
    }
}
