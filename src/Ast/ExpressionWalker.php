<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;

final readonly class ExpressionWalker
{
    /**
     * @return list<Token>
     */
    public static function references(ExpressionNodeInterface $expression): array
    {
        return self::collect($expression, fn (ExpressionNodeInterface $node): array => $node instanceof ReferenceExpressionNode
            ? [$node->token]
            : []);
    }

    /**
     * @return list<Token>
     */
    public static function customMatchers(ExpressionNodeInterface $expression): array
    {
        return self::collect($expression, fn (ExpressionNodeInterface $node): array => $node instanceof CustomMatcherExpressionNode
            ? [$node->name->token]
            : []);
    }

    /**
     * @return list<ActionExpressionNode>
     */
    public static function actionExpressions(ExpressionNodeInterface $expression): array
    {
        return self::collect($expression, fn (ExpressionNodeInterface $node): array => $node instanceof ActionExpressionNode
            ? [$node]
            : []);
    }

    /**
     * @return list<Token>
     */
    public static function labels(ExpressionNodeInterface $expression): array
    {
        return self::collect($expression, fn (ExpressionNodeInterface $node): array => $node instanceof LabeledExpressionNode
            ? [$node->label->token]
            : []);
    }

    public static function startsWithReference(ExpressionNodeInterface $expression, string $name): bool
    {
        if ($expression instanceof ExpressionNode) {
            return self::startsWithReference($expression->inner, $name);
        }

        if ($expression instanceof ActionExpressionNode) {
            return self::startsWithReference($expression->pattern, $name);
        }

        if ($expression instanceof ReferenceExpressionNode) {
            return $expression->token->value === $name;
        }

        if ($expression instanceof GroupExpressionNode || $expression instanceof QuantifiedExpressionNode) {
            return self::startsWithReference($expression->expression, $name);
        }

        if ($expression instanceof LabeledExpressionNode) {
            return self::startsWithReference($expression->expression, $name);
        }

        if ($expression instanceof SequenceExpressionNode) {
            return self::startsWithReference($expression->items[0], $name);
        }

        if ($expression instanceof ChoiceExpressionNode) {
            foreach ($expression->alternatives as $alternative) {
                if (self::startsWithReference($alternative, $name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<Token>
     */
    public static function actionReferences(ActionValueNodeInterface $action): array
    {
        if ($action instanceof IdentifierNode) {
            return [$action->token];
        }

        if ($action instanceof ActionCallNode) {
            return self::flatten(array_map(self::actionReferences(...), $action->arguments));
        }

        return [];
    }

    /**
     * @return list<ActionCallNode>
     */
    public static function actionCalls(ActionValueNodeInterface $action): array
    {
        if ($action instanceof ActionCallNode) {
            return [$action, ...self::flatten(array_map(self::actionCalls(...), $action->arguments))];
        }

        return [];
    }

    /**
     * @template T
     * @param callable(ExpressionNodeInterface): list<T> $visitor
     * @return list<T>
     */
    private static function collect(ExpressionNodeInterface $expression, callable $visitor): array
    {
        return [
            ...$visitor($expression),
            ...self::flatten(array_map(
                fn (ExpressionNodeInterface $child): array => self::collect($child, $visitor),
                self::children($expression)
            )),
        ];
    }

    /**
     * @return list<ExpressionNodeInterface>
     */
    private static function children(ExpressionNodeInterface $expression): array
    {
        if ($expression instanceof ExpressionNode) {
            return [$expression->inner];
        }

        if ($expression instanceof ActionExpressionNode) {
            return [$expression->pattern];
        }

        if ($expression instanceof ChoiceExpressionNode) {
            return $expression->alternatives;
        }

        if ($expression instanceof SequenceExpressionNode) {
            return $expression->items;
        }

        if ($expression instanceof GroupExpressionNode || $expression instanceof QuantifiedExpressionNode) {
            return [$expression->expression];
        }

        if ($expression instanceof LabeledExpressionNode) {
            return [$expression->expression];
        }

        return [];
    }

    /**
     * @template T
     * @param list<list<T>> $items
     * @return list<T>
     */
    private static function flatten(array $items): array
    {
        return array_merge(...$items);
    }
}
