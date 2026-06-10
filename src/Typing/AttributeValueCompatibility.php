<?php

declare(strict_types=1);

namespace LexiconSyntax\Typing;

use LexiconSyntax\Ast\ArrayLiteralNode;
use LexiconSyntax\Ast\AttributeValueNodeInterface;
use LexiconSyntax\Ast\BooleanLiteralNode;
use LexiconSyntax\Ast\DeclarationNodeInterface;
use LexiconSyntax\Ast\IdentifierNode;
use LexiconSyntax\Ast\NumberLiteralNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\StringLiteralNode;
use LexiconSyntax\Ast\TokenDeclarationNode;

final readonly class AttributeValueCompatibility
{
    /**
     * @param array<string, DeclarationNodeInterface> $declarations
     */
    public static function accepts(
        AttributeValueNodeInterface $value,
        SemanticTypeInterface $type,
        array $declarations
    ): bool {
        if ($type instanceof ArrayType) {
            if (!$value instanceof ArrayLiteralNode) {
                return false;
            }

            foreach ($value->items as $item) {
                if (!self::accepts($item, $type->itemType, $declarations)) {
                    return false;
                }
            }

            return true;
        }

        if ($value instanceof ArrayLiteralNode) {
            return false;
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $candidate) {
                if (self::accepts($value, $candidate, $declarations)) {
                    return true;
                }
            }

            return false;
        }

        if ($type instanceof EnumType) {
            return $value instanceof IdentifierNode
                && in_array($value->token->value, $type->cases, true);
        }

        if ($type instanceof TokenNameType) {
            return $value instanceof IdentifierNode
                && $value->token->value === $type->name
                && ($declarations[$type->name] ?? null) instanceof TokenDeclarationNode;
        }

        if ($type instanceof RuleNameType) {
            return $value instanceof IdentifierNode
                && $value->token->value === $type->name
                && ($declarations[$type->name] ?? null) instanceof RuleDeclarationNode;
        }

        if (!$type instanceof NamedType) {
            return false;
        }

        return match ($type->name) {
            'identifier' => $value instanceof IdentifierNode,
            'string' => $value instanceof StringLiteralNode,
            'number' => $value instanceof NumberLiteralNode,
            'bool' => $value instanceof BooleanLiteralNode,
            'token' => $value instanceof IdentifierNode
                && ($declarations[$value->token->value] ?? null) instanceof TokenDeclarationNode,
            'rule' => $value instanceof IdentifierNode
                && ($declarations[$value->token->value] ?? null) instanceof RuleDeclarationNode,
            default => false,
        };
    }
}
