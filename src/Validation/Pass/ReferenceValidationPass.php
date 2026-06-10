<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation\Pass;

use LexiconSyntax\Ast\ExpressionWalker;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Typing\AttributeTypeResolver;
use LexiconSyntax\Typing\SemanticTypeInspector;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class ReferenceValidationPass implements ValidationPassInterface
{
    public function validate(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $diagnostics = [];

        foreach ($document->declarations as $declaration) {
            $expression = $declaration->expression();
            if ($expression === null) {
                continue;
            }

            foreach (ExpressionWalker::references($expression) as $reference) {
                $referencedDeclaration = $index->declaration($reference->value);
                if ($referencedDeclaration === null) {
                    $type = $index->type($reference->value);
                    if ($type !== null) {
                        $resolvedType = AttributeTypeResolver::resolveExpression($type->value, $index);
                        if (!SemanticTypeInspector::isExpressionCompatible($resolvedType)) {
                            $diagnostics[] = new GrammarDiagnostic(
                                sprintf("Type '%s' cannot be used in grammar expressions.", $reference->value),
                                $reference
                            );
                        }

                        if (
                            $declaration instanceof TokenDeclarationNode
                            && SemanticTypeInspector::containsRuleName($resolvedType)
                        ) {
                            $diagnostics[] = new GrammarDiagnostic(
                                sprintf("Token '%s' cannot reference rule type '%s'.", $declaration->nameToken()->value, $reference->value),
                                $reference
                            );
                        }

                        continue;
                    }

                    $diagnostics[] = new GrammarDiagnostic(
                        sprintf("Undefined reference '%s'.", $reference->value),
                        $reference
                    );

                    continue;
                }

                if (
                    $declaration instanceof TokenDeclarationNode
                    && $referencedDeclaration instanceof RuleDeclarationNode
                ) {
                    $diagnostics[] = new GrammarDiagnostic(
                        sprintf(
                            "Token '%s' cannot reference rule '%s'.",
                            $declaration->nameToken()->value,
                            $reference->value
                        ),
                        $reference
                    );
                }
            }
        }

        return $diagnostics;
    }
}
