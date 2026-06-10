<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation;

use Lexicon\Lexer\Token;

final readonly class GrammarDiagnostic
{
    public function __construct(
        public string $message,
        public ?Token $token = null
    ) {
    }
}
