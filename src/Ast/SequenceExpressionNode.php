<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    [Part::ManyUntilRequired, LabeledExpressionNode::class, [
        GrammarTokenType::Pipe,
        GrammarTokenType::CloseParen,
        GrammarTokenType::ActionArrow,
        GrammarTokenType::Semicolon,
    ]],
], factory: 'create')]
final readonly class SequenceExpressionNode implements ExpressionNodeInterface
{
    /**
     * @param non-empty-list<ExpressionNodeInterface> $items
     */
    public function __construct(public array $items)
    {
    }

    /**
     * @param non-empty-list<ExpressionNodeInterface> $items
     */
    public static function create(array $items): ExpressionNodeInterface
    {
        return count($items) === 1 ? $items[0] : new self($items);
    }
}
