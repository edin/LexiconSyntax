<?php

declare(strict_types=1);

namespace LexiconSyntax\Metadata;

use LexiconSyntax\Ast\ArrayLiteralNode;
use LexiconSyntax\Ast\BooleanLiteralNode;
use LexiconSyntax\Ast\AttributeNode;
use LexiconSyntax\Ast\AttributeValueNodeInterface;
use LexiconSyntax\Ast\DeclarationNodeInterface;
use LexiconSyntax\Ast\IdentifierNode;
use LexiconSyntax\Ast\NumberLiteralNode;
use LexiconSyntax\Ast\StringLiteralNode;

final readonly class MetadataResolver
{
    public static function forDeclaration(DeclarationNodeInterface $declaration): MetadataBag
    {
        return self::fromAttributes($declaration->attributes());
    }

    /**
     * @param list<AttributeNode> $attributes
     */
    public static function fromAttributes(array $attributes): MetadataBag
    {
        $resolved = [];

        foreach ($attributes as $attribute) {
            $name = $attribute->name->token->value;
            $arguments = [];
            $positional = [];

            foreach ($attribute->arguments as $index => $argument) {
                $value = self::value($argument->value);
                $positional[] = $value;

                if ($argument->name !== null) {
                    $arguments[$argument->name->token->value] = $value;
                    continue;
                }

                $arguments[(string) $index] = $value;
            }

            $resolved[$name] = new ResolvedAttribute($name, $arguments, $positional);
        }

        return new MetadataBag($resolved);
    }

    private static function value(AttributeValueNodeInterface $value): mixed
    {
        if ($value instanceof ArrayLiteralNode) {
            return array_map(self::value(...), $value->items);
        }

        if ($value instanceof BooleanLiteralNode) {
            return $value->token->value === 'true';
        }

        if ($value instanceof IdentifierNode) {
            return $value->token->value;
        }

        if ($value instanceof NumberLiteralNode) {
            return str_contains($value->token->value, '.')
                ? (float) $value->token->value
                : (int) $value->token->value;
        }

        if ($value instanceof StringLiteralNode) {
            return stripcslashes(substr($value->token->value, 1, -1));
        }

        return null;
    }
}
