<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    [Part::SeparatedByRequired, AlternativeExpressionNode::class, GrammarTokenType::Pipe],
], factory: 'create')]
final readonly class ChoiceExpressionNode implements ExpressionNodeInterface
{
    /**
     * @param non-empty-list<ExpressionNodeInterface> $alternatives
     */
    public function __construct(public array $alternatives)
    {
    }

    /**
     * @param non-empty-list<ExpressionNodeInterface> $alternatives
     */
    public static function create(array $alternatives): ExpressionNodeInterface
    {
        return count($alternatives) === 1 ? $alternatives[0] : new self($alternatives);
    }
}
