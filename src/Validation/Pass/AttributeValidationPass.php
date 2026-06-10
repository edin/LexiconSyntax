<?php

declare(strict_types=1);

namespace LexiconSyntax\Validation\Pass;

use Lexicon\Lexer\Location;
use Lexicon\Lexer\SourceFile;
use Lexicon\Lexer\Token;
use Lexicon\Lexer\TokenGroup;
use LexiconSyntax\Ast\ArrayLiteralNode;
use LexiconSyntax\Ast\AttributeArgumentNode;
use LexiconSyntax\Ast\AttributeDeclarationNode;
use LexiconSyntax\Ast\AttributeNode;
use LexiconSyntax\Ast\AttributeParameterNode;
use LexiconSyntax\Ast\AttributeValueNodeInterface;
use LexiconSyntax\Ast\BooleanLiteralNode;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\IdentifierNode;
use LexiconSyntax\Ast\NumberLiteralNode;
use LexiconSyntax\Ast\StringLiteralNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\GrammarTokenType;
use LexiconSyntax\Typing\AttributeTypeResolver;
use LexiconSyntax\Typing\AttributeValueCompatibility;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class AttributeValidationPass implements ValidationPassInterface
{
    public function validate(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $diagnostics = [];

        foreach ($document->declarations as $declaration) {
            foreach ($declaration->attributes() as $attribute) {
                $diagnostics = [
                    ...$diagnostics,
                    ...self::validateAttribute($attribute, $declaration->kind(), $index),
                ];
            }
        }

        return $diagnostics;
    }

    /**
     * @return list<GrammarDiagnostic>
     */
    private static function validateAttribute(AttributeNode $attribute, string $target, GrammarIndex $index): array
    {
        $name = $attribute->name->token->value;
        $schema = $index->attributeSchema($target, $name);
        if ($schema === null) {
            if ($index->hasAttributeWithName($name)) {
                return [new GrammarDiagnostic(
                    sprintf("Attribute '%s' cannot be used on %s.", $name, $target),
                    $attribute->name->token
                )];
            }

            return [new GrammarDiagnostic(sprintf("Unknown attribute '%s'.", $name), $attribute->name->token)];
        }

        return self::validateAttributeArguments($attribute, $schema, $index);
    }

    /**
     * @return list<GrammarDiagnostic>
     */
    private static function validateAttributeArguments(
        AttributeNode $attribute,
        AttributeDeclarationNode $schema,
        GrammarIndex $index
    ): array {
        $diagnostics = [];
        $parameters = $schema->parameters;
        $arguments = [];

        foreach ($attribute->arguments as $argumentIndex => $argument) {
            $parameter = $argument->name === null ? ($parameters[$argumentIndex] ?? null) : self::parameterByName($parameters, $argument->name->token->value);
            if ($parameter === null) {
                $diagnostics[] = new GrammarDiagnostic(
                    $argument->name === null
                        ? sprintf("Attribute '%s' does not accept positional argument %d.", $attribute->name->token->value, $argumentIndex + 1)
                        : sprintf("Attribute '%s' has no parameter '%s'.", $attribute->name->token->value, $argument->name->token->value),
                    self::attributeArgumentToken($argument)
                );
                continue;
            }

            $parameterName = $parameter->name->token->value;
            if (isset($arguments[$parameterName])) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Attribute '%s' parameter '%s' is already set.", $attribute->name->token->value, $parameterName),
                    self::attributeArgumentToken($argument)
                );
                continue;
            }

            $arguments[$parameterName] = true;
            $diagnostics = [
                ...$diagnostics,
                ...self::validateAttributeValue($attribute, $parameter, $argument->value, $index),
            ];
        }

        foreach ($parameters as $parameter) {
            if (!isset($arguments[$parameter->name->token->value])) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Attribute '%s' requires parameter '%s'.", $attribute->name->token->value, $parameter->name->token->value),
                    $attribute->name->token
                );
            }
        }

        return $diagnostics;
    }

    /**
     * @param list<AttributeParameterNode> $parameters
     */
    private static function parameterByName(array $parameters, string $name): ?AttributeParameterNode
    {
        foreach ($parameters as $parameter) {
            if ($parameter->name->token->value === $name) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * @return list<GrammarDiagnostic>
     */
    private static function validateAttributeValue(
        AttributeNode $attribute,
        AttributeParameterNode $parameter,
        AttributeValueNodeInterface $value,
        GrammarIndex $index
    ): array {
        $type = AttributeTypeResolver::resolve($parameter->type, $index);
        $valid = AttributeValueCompatibility::accepts($value, $type, $index->declarations());

        return $valid ? [] : [new GrammarDiagnostic(
            sprintf("Attribute '%s' parameter '%s' expects %s.", $attribute->name->token->value, $parameter->name->token->value, $type->display()),
            self::attributeValueToken($value)
        )];
    }

    private static function attributeArgumentToken(AttributeArgumentNode $argument): Token
    {
        if ($argument->name !== null) {
            return $argument->name->token;
        }

        return self::attributeValueToken($argument->value);
    }

    private static function attributeValueToken(AttributeValueNodeInterface $value): Token
    {
        if ($value instanceof ArrayLiteralNode) {
            return $value->open;
        }

        if ($value instanceof IdentifierNode) {
            return $value->token;
        }

        if (
            $value instanceof StringLiteralNode
            || $value instanceof NumberLiteralNode
            || $value instanceof BooleanLiteralNode
        ) {
            return $value->token;
        }

        return self::syntheticToken('<unknown>');
    }

    private static function syntheticToken(string $value): Token
    {
        return new Token(
            GrammarTokenType::Identifier,
            $value,
            new Location(new SourceFile('<schema>', ''), 0, 1, 1),
            TokenGroup::Literal
        );
    }
}
