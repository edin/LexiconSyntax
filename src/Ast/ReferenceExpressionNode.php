<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;

#[Sequence([IdentifierNode::class], factory: 'create')]
final readonly class ReferenceExpressionNode implements PrimaryExpressionNodeInterface
{
    public function __construct(public Token $token)
    {
    }

    public static function create(IdentifierNode $identifier): self
    {
        return new self($identifier->token);
    }
}
