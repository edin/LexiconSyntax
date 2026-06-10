<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    GrammarTokenType::NodeKeyword,
    IdentifierNode::class,
    [Part::OptionalSequence, GrammarTokenType::Colon, IdentifierNode::class],
    [Part::Optional, [Part::ListBetween, AttributeParameterNode::class, GrammarTokenType::Comma, GrammarTokenType::OpenParen, GrammarTokenType::CloseParen, true]],
    GrammarTokenType::Semicolon,
])]
final readonly class NodeDeclarationNode
{
    /** @var list<AttributeParameterNode> */
    public array $fields;
    public ?IdentifierNode $parent;

    /**
     * @param list<AttributeParameterNode>|null $fields
     * @param array{0: Token, 1: IdentifierNode}|null $parent
     */
    public function __construct(
        public Token $keyword,
        public IdentifierNode $name,
        ?array $parent,
        ?array $fields,
        public Token $semicolon
    ) {
        $this->parent = $parent[1] ?? null;
        $this->fields = $fields ?? [];
    }

    public function parentName(): ?string
    {
        return $this->parent?->token->value;
    }
}
