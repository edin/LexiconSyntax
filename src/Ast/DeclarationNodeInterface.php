<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;

interface DeclarationNodeInterface
{
    /**
     * @return list<AttributeNode>
     */
    public function attributes(): array;

    public function nameToken(): Token;

    public function expression(): ?ExpressionNodeInterface;

    public function kind(): string;
}
