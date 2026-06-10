<?php

declare(strict_types=1);

namespace LexiconSyntax\Typing;

use LexiconSyntax\Ast\AttributeTypeNode;
use LexiconSyntax\Ast\TypeExpressionNode;
use LexiconSyntax\GrammarIndex;

final readonly class AttributeTypeResolver
{
    /**
     * @param list<string> $seen
     */
    public static function resolve(AttributeTypeNode $type, ?GrammarIndex $index = null, array $seen = []): SemanticTypeInterface
    {
        $name = $type->name->token->value;
        $baseType = $name === 'enum'
            ? new EnumType(array_map(
                fn ($value): string => $value->token->value,
                $type->enumValues
            ))
            : self::resolveNamed($name, $index, $seen);

        return $type->isArray() ? new ArrayType($baseType) : $baseType;
    }

    /**
     * @param list<string> $seen
     */
    public static function resolveExpression(TypeExpressionNode $expression, ?GrammarIndex $index = null, array $seen = []): SemanticTypeInterface
    {
        $types = array_map(
            fn (AttributeTypeNode $type): SemanticTypeInterface => self::resolve($type, $index, $seen),
            $expression->alternatives
        );

        return count($types) === 1 ? $types[0] : new UnionType($types);
    }

    /**
     * @param list<string> $seen
     */
    private static function resolveNamed(string $name, ?GrammarIndex $index, array $seen): SemanticTypeInterface
    {
        if ($index === null) {
            return new NamedType($name);
        }

        $alias = $index->type($name);
        if ($alias !== null && !in_array($name, $seen, true)) {
            return self::resolveExpression($alias->value, $index, [...$seen, $name]);
        }

        if ($index->token($name) !== null) {
            return new TokenNameType($name);
        }

        if ($index->rule($name) !== null) {
            return new RuleNameType($name);
        }

        if ($index->node($name) !== null) {
            return new NodeNameType($name);
        }

        return new NamedType($name);
    }
}
