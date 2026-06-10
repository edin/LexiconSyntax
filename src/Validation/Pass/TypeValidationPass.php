<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation\Pass;

use LexiconSyntax\Ast\AttributeDeclarationNode;
use LexiconSyntax\Ast\AttributeTypeNode;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\TypeDeclarationNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class TypeValidationPass implements ValidationPassInterface
{
    private const BUILT_IN_TYPES = ['identifier', 'string', 'number', 'bool', 'token', 'rule'];

    public function validate(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $diagnostics = [];

        foreach ($document->typeDeclarations as $declaration) {
            foreach ($declaration->value->alternatives as $type) {
                $diagnostics = [
                    ...$diagnostics,
                    ...self::validateTypeNode($type, $index, [$declaration->name->token->value]),
                ];
            }
        }

        foreach ($document->attributeDeclarations as $declaration) {
            $diagnostics = [
                ...$diagnostics,
                ...self::validateAttributeDeclaration($declaration, $index),
            ];
        }

        return $diagnostics;
    }

    /**
     * @return list<GrammarDiagnostic>
     */
    private static function validateAttributeDeclaration(AttributeDeclarationNode $declaration, GrammarIndex $index): array
    {
        $diagnostics = [];

        foreach ($declaration->parameters as $parameter) {
            $diagnostics = [
                ...$diagnostics,
                ...self::validateTypeNode($parameter->type, $index, []),
            ];
        }

        return $diagnostics;
    }

    /**
     * @param list<string> $seen
     * @return list<GrammarDiagnostic>
     */
    private static function validateTypeNode(AttributeTypeNode $type, GrammarIndex $index, array $seen): array
    {
        $name = $type->name->token->value;
        if ($name === 'enum') {
            return [];
        }

        if (in_array($name, self::BUILT_IN_TYPES, true)) {
            return [];
        }

        if ($index->token($name) !== null || $index->rule($name) !== null) {
            return [];
        }

        $alias = $index->type($name);
        if ($alias === null) {
            return [new GrammarDiagnostic(sprintf("Unknown type '%s'.", $name), $type->name->token)];
        }

        if (in_array($name, $seen, true)) {
            return [new GrammarDiagnostic(sprintf("Type '%s' recursively references itself.", $name), $type->name->token)];
        }

        return self::validateTypeDeclaration($alias, $index, [...$seen, $name]);
    }

    /**
     * @param list<string> $seen
     * @return list<GrammarDiagnostic>
     */
    private static function validateTypeDeclaration(TypeDeclarationNode $declaration, GrammarIndex $index, array $seen): array
    {
        $diagnostics = [];

        foreach ($declaration->value->alternatives as $type) {
            $diagnostics = [
                ...$diagnostics,
                ...self::validateTypeNode($type, $index, $seen),
            ];
        }

        return $diagnostics;
    }
}
