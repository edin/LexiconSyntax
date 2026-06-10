<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    GrammarTokenType::ImportKeyword,
    StringLiteralNode::class,
    GrammarTokenType::Semicolon,
])]
final readonly class ImportDeclarationNode
{
    public function __construct(
        public Token $keyword,
        public StringLiteralNode $path,
        public Token $semicolon
    ) {
    }

    public function pathValue(): string
    {
        return stripcslashes(substr($this->path->token->value, 1, -1));
    }
}
