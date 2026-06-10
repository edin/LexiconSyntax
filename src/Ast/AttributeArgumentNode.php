<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    IdentifierNode::class,
    GrammarTokenType::Colon,
    AttributeValueNodeInterface::class,
], factory: 'named')]
#[Sequence([AttributeValueNodeInterface::class], factory: 'positional')]
final readonly class AttributeArgumentNode
{
    public function __construct(
        public ?IdentifierNode $name,
        public ?Token $colon,
        public AttributeValueNodeInterface $value
    ) {
    }

    public static function named(
        IdentifierNode $name,
        Token $colon,
        AttributeValueNodeInterface $value
    ): self
    {
        return new self($name, $colon, $value);
    }

    public static function positional(AttributeValueNodeInterface $value): self
    {
        return new self(null, null, $value);
    }
}
