<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Terminal;
use LexiconSyntax\GrammarTokenType;

#[Terminal(GrammarTokenType::NumberLiteral, 'Expected number.')]
final readonly class NumberLiteralNode implements AttributeValueNodeInterface, ActionValueNodeInterface
{
    public function __construct(public Token $token)
    {
    }
}
