<?php

declare(strict_types=1);

namespace LexiconSyntax\Typing;

final readonly class SemanticTypeInspector
{
    public static function isExpressionCompatible(SemanticTypeInterface $type): bool
    {
        if ($type instanceof TokenNameType || $type instanceof RuleNameType) {
            return true;
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $candidate) {
                if (!self::isExpressionCompatible($candidate)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public static function containsRuleName(SemanticTypeInterface $type): bool
    {
        if ($type instanceof RuleNameType) {
            return true;
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $candidate) {
                if (self::containsRuleName($candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function symbolNames(SemanticTypeInterface $type): array
    {
        if ($type instanceof TokenNameType || $type instanceof RuleNameType) {
            return [$type->name];
        }

        if ($type instanceof UnionType) {
            return array_merge(...array_map(self::symbolNames(...), $type->types));
        }

        return [];
    }
}
