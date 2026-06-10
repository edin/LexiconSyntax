<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    GrammarTokenType::OpenParen,
    ChoiceExpressionNode::class,
    GrammarTokenType::CloseParen,
], factory: 'create')]
final readonly class GroupExpressionNode implements PrimaryExpressionNodeInterface
{
    public function __construct(public ExpressionNodeInterface $expression)
    {
    }

    public static function create(
        Token $open,
        ExpressionNodeInterface $expression,
        Token $close
    ): self {
        return new self($expression);
    }
}
