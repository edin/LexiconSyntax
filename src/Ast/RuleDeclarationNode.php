<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\PrefixMany;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[PrefixMany(AttributeNode::class)]
#[Sequence([
    GrammarTokenType::RuleKeyword,
    IdentifierNode::class,
    [\Lexicon\Parser\Part::OptionalSequence, GrammarTokenType::Colon, TypeExpressionNode::class],
    GrammarTokenType::Define,
    ExpressionNode::class,
    GrammarTokenType::Semicolon,
])]
final readonly class RuleDeclarationNode implements DeclarationNodeInterface
{
    public ?TypeExpressionNode $returnType;

    /**
     * @param list<AttributeNode> $attributes
     * @param array{0: Token, 1: TypeExpressionNode}|null $returnType
     */
    public function __construct(
        public array $attributes,
        public Token $keyword,
        public IdentifierNode $name,
        ?array $returnType,
        public Token $define,
        public ExpressionNodeInterface $value,
        public Token $semicolon
    ) {
        $this->returnType = $returnType[1] ?? null;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function nameToken(): Token
    {
        return $this->name->token;
    }

    public function expression(): ExpressionNodeInterface
    {
        return $this->value;
    }

    public function kind(): string
    {
        return 'rule';
    }
}
