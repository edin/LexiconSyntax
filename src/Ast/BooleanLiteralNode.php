<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([GrammarTokenType::TrueKeyword], factory: 'create')]
#[Sequence([GrammarTokenType::FalseKeyword], factory: 'create')]
final readonly class BooleanLiteralNode implements AttributeValueNodeInterface, ActionValueNodeInterface
{
    public function __construct(public Token $token)
    {
    }

    public static function create(Token $token): self
    {
        return new self($token);
    }
}
