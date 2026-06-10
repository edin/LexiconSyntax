<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Terminal;
use LexiconSyntax\GrammarTokenType;

#[Terminal(GrammarTokenType::Identifier, 'Expected identifier.')]
final readonly class IdentifierNode implements AttributeValueNodeInterface, ActionValueNodeInterface
{
    public function __construct(public Token $token)
    {
    }
}
