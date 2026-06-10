<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    IdentifierNode::class,
    GrammarTokenType::Colon,
    PostfixExpressionNode::class,
], factory: 'create')]
#[Sequence([PostfixExpressionNode::class], factory: 'unlabeled')]
final readonly class LabeledExpressionNode implements ExpressionNodeInterface
{
    public function __construct(
        public IdentifierNode $label,
        public Token $colon,
        public ExpressionNodeInterface $expression
    ) {
    }

    public static function create(
        IdentifierNode $label,
        Token $colon,
        ExpressionNodeInterface $expression
    ): self {
        return new self($label, $colon, $expression);
    }

    public static function unlabeled(ExpressionNodeInterface $expression): ExpressionNodeInterface
    {
        return $expression;
    }
}
