<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation\Pass;

use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class TokenValidationPass implements ValidationPassInterface
{
    private const TOKEN_CATEGORIES = ['keyword', 'symbol', 'literal', 'trivia', 'unknown', 'eof'];

    public function validate(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $diagnostics = [];

        foreach ($document->declarations as $declaration) {
            if (!$declaration instanceof TokenDeclarationNode) {
                continue;
            }

            $category = $declaration->categoryName();
            $expression = $declaration->expression();

            if ($category !== null && !in_array($category, self::TOKEN_CATEGORIES, true)) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Unknown token category '%s'.", $category),
                    $declaration->category->token
                );
            }

            if ($expression === null && !in_array($category, ['eof', 'unknown'], true)) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Token '%s' must define an expression unless it is eof or unknown.", $declaration->nameToken()->value),
                    $declaration->nameToken()
                );
            }

            if ($expression !== null && in_array($category, ['eof', 'unknown'], true)) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Token '%s' cannot define an expression when declared as %s.", $declaration->nameToken()->value, $category),
                    $declaration->nameToken()
                );
            }
        }

        return $diagnostics;
    }
}
