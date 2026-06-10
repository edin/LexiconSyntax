<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Parser\Attributes\OneOf;

#[OneOf([
    ArrayLiteralNode::class,
    StringLiteralNode::class,
    NumberLiteralNode::class,
    BooleanLiteralNode::class,
    IdentifierNode::class,
])]
interface AttributeValueNodeInterface
{
}
