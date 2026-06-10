<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation\Pass;

use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Validation\GrammarDiagnostic;

interface ValidationPassInterface
{
    /**
     * @return list<GrammarDiagnostic>
     */
    public function validate(GrammarDocumentNode $document, GrammarIndex $index): array;
}
