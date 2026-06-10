<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    [Part::SeparatedByRequired, AttributeTypeNode::class, GrammarTokenType::Pipe],
], factory: 'create')]
final readonly class TypeExpressionNode
{
    /**
     * @param non-empty-list<AttributeTypeNode> $alternatives
     */
    public function __construct(public array $alternatives)
    {
    }

    /**
     * @param non-empty-list<AttributeTypeNode> $alternatives
     */
    public static function create(array $alternatives): self
    {
        return new self($alternatives);
    }
}
