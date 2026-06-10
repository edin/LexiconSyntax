<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Parser\Attributes\Sequence;

#[Sequence([ChoiceExpressionNode::class])]
final readonly class ExpressionNode implements ExpressionNodeInterface
{
    public function __construct(public ExpressionNodeInterface $inner)
    {
    }
}
