<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering;

use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class LoweringResult
{
    /**
     * @param list<GrammarDiagnostic> $diagnostics
     */
    public function __construct(
        public LoweredGrammar $grammar,
        public array $diagnostics
    ) {
    }

    public function hasErrors(): bool
    {
        return $this->diagnostics !== [];
    }
}
