<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation\Pass;

use LexiconSyntax\Ast\DeclarationNodeInterface;
use LexiconSyntax\Ast\ExpressionWalker;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Typing\AttributeTypeResolver;
use LexiconSyntax\Typing\SemanticTypeInspector;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class ReachabilityValidationPass implements ValidationPassInterface
{
    public function validate(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $diagnostics = [];

        foreach (self::unreachable($document, $index) as $declaration) {
            $diagnostics[] = new GrammarDiagnostic(
                sprintf("Declaration '%s' is unreachable.", $declaration->nameToken()->value),
                $declaration->nameToken()
            );
        }

        return $diagnostics;
    }

    /**
     * @return list<DeclarationNodeInterface>
     */
    private static function unreachable(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $start = null;
        foreach ($document->declarations as $declaration) {
            if ($declaration instanceof RuleDeclarationNode && self::hasStartAttribute($declaration)) {
                $start = $declaration;
                break;
            }
        }

        foreach ($document->declarations as $declaration) {
            if ($start === null && $declaration instanceof RuleDeclarationNode) {
                $start = $declaration;
                break;
            }
        }

        $start ??= $document->declarations[0] ?? null;
        if ($start === null) {
            return [];
        }

        $reachable = [];
        $pending = [$start->nameToken()->value];

        while ($pending !== []) {
            $name = array_pop($pending);
            $declaration = $index->declaration($name);
            if (isset($reachable[$name]) || $declaration === null) {
                continue;
            }

            $reachable[$name] = true;
            $expression = $declaration->expression();
            if ($expression === null) {
                continue;
            }

            foreach (ExpressionWalker::references($expression) as $reference) {
                $type = $index->type($reference->value);
                if ($type === null) {
                    $pending[] = $reference->value;
                    continue;
                }

                $pending = [
                    ...$pending,
                    ...SemanticTypeInspector::symbolNames(AttributeTypeResolver::resolveExpression($type->value, $index)),
                ];
            }
        }

        return array_values(array_filter(
            $document->declarations,
            fn (DeclarationNodeInterface $declaration): bool => !isset($reachable[$declaration->nameToken()->value])
                && !self::isGeneratedToken($declaration)
        ));
    }

    private static function isGeneratedToken(DeclarationNodeInterface $declaration): bool
    {
        return $declaration instanceof TokenDeclarationNode
            && in_array($declaration->categoryName(), ['eof', 'unknown', 'trivia'], true);
    }

    private static function hasStartAttribute(RuleDeclarationNode $declaration): bool
    {
        foreach ($declaration->attributes as $attribute) {
            if ($attribute->name->token->value === 'start') {
                return true;
            }
        }

        return false;
    }
}
