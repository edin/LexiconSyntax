<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    AttributeTypeNameNode::class,
    [Part::ListBetween, IdentifierNode::class, GrammarTokenType::Comma, GrammarTokenType::OpenBracket, GrammarTokenType::CloseBracket],
    GrammarTokenType::OpenBracket,
    GrammarTokenType::CloseBracket,
], factory: 'withEnumValuesAndArray')]
#[Sequence([
    AttributeTypeNameNode::class,
    [Part::ListBetween, IdentifierNode::class, GrammarTokenType::Comma, GrammarTokenType::OpenBracket, GrammarTokenType::CloseBracket],
], factory: 'withEnumValues')]
#[Sequence([
    AttributeTypeNameNode::class,
    GrammarTokenType::OpenBracket,
    GrammarTokenType::CloseBracket,
], factory: 'array')]
#[Sequence([AttributeTypeNameNode::class], factory: 'named')]
final readonly class AttributeTypeNode
{
    /**
     * @param list<IdentifierNode> $enumValues
     */
    public function __construct(
        public IdentifierNode $name,
        public array $enumValues,
        public ?Token $arrayOpen,
        public ?Token $arrayClose
    ) {
    }

    /**
     * @param list<IdentifierNode> $enumValues
     */
    public static function withEnumValuesAndArray(
        AttributeTypeNameNode $name,
        array $enumValues,
        Token $arrayOpen,
        Token $arrayClose
    ): ?self
    {
        if ($name->name->token->value !== 'enum') {
            return null;
        }

        return new self($name->name, $enumValues, $arrayOpen, $arrayClose);
    }

    /**
     * @param list<IdentifierNode> $enumValues
     */
    public static function withEnumValues(AttributeTypeNameNode $name, array $enumValues): ?self
    {
        if ($name->name->token->value !== 'enum') {
            return null;
        }

        return new self($name->name, $enumValues, null, null);
    }

    public static function array(
        AttributeTypeNameNode $name,
        Token $arrayOpen,
        Token $arrayClose
    ): self {
        return new self($name->name, [], $arrayOpen, $arrayClose);
    }

    public static function named(AttributeTypeNameNode $name): self
    {
        return new self($name->name, [], null, null);
    }

    public function isArray(): bool
    {
        return $this->arrayOpen !== null && $this->arrayClose !== null;
    }
}
