<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Parser\Attributes\Sequence;

#[Sequence([StringLiteralNode::class])]
final readonly class StringLiteralExpressionNode implements PrimaryExpressionNodeInterface
{
    public function __construct(public StringLiteralNode $literal)
    {
    }
}
