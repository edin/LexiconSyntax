<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation\Pass;

use LexiconSyntax\Ast\ExpressionWalker;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class ActionValidationPass implements ValidationPassInterface
{
    public function validate(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $diagnostics = [];

        foreach ($document->declarations as $declaration) {
            $expression = $declaration->expression();
            if ($expression === null) {
                continue;
            }

            if ($declaration instanceof TokenDeclarationNode) {
                foreach (ExpressionWalker::actionExpressions($expression) as $actionExpression) {
                    $diagnostics[] = new GrammarDiagnostic(
                        sprintf("Token '%s' cannot define an action.", $declaration->nameToken()->value),
                        $actionExpression->arrow
                    );
                }

                continue;
            }

            if (!$declaration instanceof RuleDeclarationNode) {
                continue;
            }

            foreach (ExpressionWalker::actionExpressions($expression) as $actionExpression) {
                $labels = [];
                foreach (ExpressionWalker::labels($actionExpression->pattern) as $label) {
                    if (isset($labels[$label->value])) {
                        $diagnostics[] = new GrammarDiagnostic(
                            sprintf("Duplicate action label '%s'.", $label->value),
                            $label
                        );
                    }

                    $labels[$label->value] = true;
                }

                foreach (ExpressionWalker::actionReferences($actionExpression->action) as $reference) {
                    if (!isset($labels[$reference->value])) {
                        $diagnostics[] = new GrammarDiagnostic(
                            sprintf("Undefined action binding '%s'.", $reference->value),
                            $reference
                        );
                    }
                }

                foreach (ExpressionWalker::actionCalls($actionExpression->action) as $call) {
                    $node = $index->node($call->name->token->value);
                    if ($node === null) {
                        $diagnostics[] = new GrammarDiagnostic(
                            sprintf("Unknown node '%s'.", $call->name->token->value),
                            $call->name->token
                        );
                        continue;
                    }

                    if (count($call->arguments) !== count($node->fields)) {
                        $diagnostics[] = new GrammarDiagnostic(
                            sprintf(
                                "Node '%s' expects %d arguments, got %d.",
                                $call->name->token->value,
                                count($node->fields),
                                count($call->arguments)
                            ),
                            $call->name->token
                        );
                    }
                }
            }
        }

        return $diagnostics;
    }
}
