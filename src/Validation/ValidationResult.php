<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation;

final readonly class ValidationResult
{
    /**
     * @param list<GrammarDiagnostic> $diagnostics
     */
    public function __construct(public array $diagnostics)
    {
    }

    public function hasErrors(): bool
    {
        return $this->diagnostics !== [];
    }
}
