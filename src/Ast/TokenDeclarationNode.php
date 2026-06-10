<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\PrefixMany;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[PrefixMany(AttributeNode::class)]
#[Sequence([
    GrammarTokenType::TokenKeyword,
    IdentifierNode::class,
    IdentifierNode::class,
    GrammarTokenType::Define,
    ExpressionNode::class,
    GrammarTokenType::Semicolon,
], factory: 'categorizedDefined')]
#[Sequence([
    GrammarTokenType::TokenKeyword,
    IdentifierNode::class,
    GrammarTokenType::Define,
    ExpressionNode::class,
    GrammarTokenType::Semicolon,
], factory: 'defined')]
#[Sequence([
    GrammarTokenType::TokenKeyword,
    IdentifierNode::class,
    IdentifierNode::class,
    GrammarTokenType::Semicolon,
], factory: 'categorizedDeclared')]
#[Sequence([
    GrammarTokenType::TokenKeyword,
    IdentifierNode::class,
    GrammarTokenType::Semicolon,
], factory: 'declared')]
final readonly class TokenDeclarationNode implements DeclarationNodeInterface
{
    /**
     * @param list<AttributeNode> $attributes
     */
    public function __construct(
        public array $attributes,
        public Token $keyword,
        public ?IdentifierNode $category,
        public IdentifierNode $name,
        public ?Token $define,
        public ?ExpressionNodeInterface $value,
        public Token $semicolon
    ) {
    }

    /**
     * @param list<AttributeNode> $attributes
     */
    public static function categorizedDefined(
        array $attributes,
        Token $keyword,
        IdentifierNode $category,
        IdentifierNode $name,
        Token $define,
        ExpressionNodeInterface $value,
        Token $semicolon
    ): self {
        return new self($attributes, $keyword, $category, $name, $define, $value, $semicolon);
    }

    /**
     * @param list<AttributeNode> $attributes
     */
    public static function defined(
        array $attributes,
        Token $keyword,
        IdentifierNode $name,
        Token $define,
        ExpressionNodeInterface $value,
        Token $semicolon
    ): self {
        return new self($attributes, $keyword, null, $name, $define, $value, $semicolon);
    }

    /**
     * @param list<AttributeNode> $attributes
     */
    public static function categorizedDeclared(
        array $attributes,
        Token $keyword,
        IdentifierNode $category,
        IdentifierNode $name,
        Token $semicolon
    ): self {
        return new self($attributes, $keyword, $category, $name, null, null, $semicolon);
    }

    /**
     * @param list<AttributeNode> $attributes
     */
    public static function declared(
        array $attributes,
        Token $keyword,
        IdentifierNode $name,
        Token $semicolon
    ): self {
        return new self($attributes, $keyword, null, $name, null, null, $semicolon);
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function categoryName(): ?string
    {
        return $this->category?->token->value;
    }

    public function nameToken(): Token
    {
        return $this->name->token;
    }

    public function expression(): ?ExpressionNodeInterface
    {
        return $this->value;
    }

    public function kind(): string
    {
        return 'token';
    }
}
