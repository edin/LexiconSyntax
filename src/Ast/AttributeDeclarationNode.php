<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    GrammarTokenType::AttributeKeyword,
    AttributeTargetNode::class,
    IdentifierNode::class,
    [Part::Optional, [Part::ListBetween, AttributeParameterNode::class, GrammarTokenType::Comma, GrammarTokenType::OpenParen, GrammarTokenType::CloseParen, true]],
    GrammarTokenType::Semicolon,
])]
final readonly class AttributeDeclarationNode
{
    public Token $target;

    /** @var list<AttributeParameterNode> */
    public array $parameters;

    /**
     * @param list<AttributeParameterNode>|null $parameters
     */
    public function __construct(
        public Token $keyword,
        Token|AttributeTargetNode $target,
        public IdentifierNode $name,
        ?array $parameters,
        public Token $semicolon
    ) {
        $this->target = $target instanceof AttributeTargetNode ? $target->token : $target;
        $this->parameters = $parameters ?? [];
    }

    public function targetName(): string
    {
        return $this->target->value;
    }
}
