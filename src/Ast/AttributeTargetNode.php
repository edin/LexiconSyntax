<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([GrammarTokenType::TokenKeyword], factory: 'fromToken')]
#[Sequence([GrammarTokenType::RuleKeyword], factory: 'fromToken')]
#[Sequence([GrammarTokenType::GrammarKeyword], factory: 'fromToken')]
#[Sequence([IdentifierNode::class], factory: 'fromIdentifier')]
final readonly class AttributeTargetNode
{
    public function __construct(public Token $token)
    {
    }

    public static function fromToken(Token $token): self
    {
        return new self($token);
    }

    public static function fromIdentifier(IdentifierNode $identifier): self
    {
        return new self($identifier->token);
    }
}
