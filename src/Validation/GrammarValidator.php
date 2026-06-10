<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation;

use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Validation\Pass\ActionValidationPass;
use LexiconSyntax\Validation\Pass\AttributeValidationPass;
use LexiconSyntax\Validation\Pass\ReachabilityValidationPass;
use LexiconSyntax\Validation\Pass\ReferenceValidationPass;
use LexiconSyntax\Validation\Pass\RuleValidationPass;
use LexiconSyntax\Validation\Pass\TokenValidationPass;
use LexiconSyntax\Validation\Pass\TypeValidationPass;
use LexiconSyntax\Validation\Pass\ValidationPassInterface;

final readonly class GrammarValidator
{
    public static function validate(GrammarDocumentNode $document): ValidationResult
    {
        $index = GrammarIndex::from($document);
        $diagnostics = $index->diagnostics;

        foreach (self::passes() as $pass) {
            $diagnostics = [
                ...$diagnostics,
                ...$pass->validate($document, $index),
            ];
        }

        return new ValidationResult($diagnostics);
    }

    /**
     * @return list<ValidationPassInterface>
     */
    private static function passes(): array
    {
        return [
            new TypeValidationPass(),
            new AttributeValidationPass(),
            new TokenValidationPass(),
            new ReferenceValidationPass(),
            new RuleValidationPass(),
            new ActionValidationPass(),
            new ReachabilityValidationPass(),
        ];
    }
}
