<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation\Pass;

use LexiconSyntax\Ast\ExpressionWalker;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class RuleValidationPass implements ValidationPassInterface
{
    public function validate(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $diagnostics = [];

        foreach ($document->declarations as $declaration) {
            if (!$declaration instanceof RuleDeclarationNode) {
                continue;
            }

            $expression = $declaration->expression();

            foreach (ExpressionWalker::customMatchers($expression) as $matcher) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf(
                        "Rule '%s' cannot use custom matcher '%s'.",
                        $declaration->nameToken()->value,
                        $matcher->value
                    ),
                    $matcher
                );
            }

            if (ExpressionWalker::startsWithReference($expression, $declaration->nameToken()->value)) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Rule '%s' is directly left-recursive.", $declaration->nameToken()->value),
                    $declaration->nameToken()
                );
            }
        }

        return $diagnostics;
    }
}
