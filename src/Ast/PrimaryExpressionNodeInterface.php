<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Parser\Attributes\OneOf;

#[OneOf([
    GroupExpressionNode::class,
    CharacterRangeExpressionNode::class,
    StringLiteralExpressionNode::class,
    CustomMatcherExpressionNode::class,
    ReferenceExpressionNode::class,
])]
interface PrimaryExpressionNodeInterface extends ExpressionNodeInterface
{
}
