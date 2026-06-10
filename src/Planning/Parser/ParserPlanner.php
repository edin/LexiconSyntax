<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser;

use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Lowering\Action\ConstructNodeAction;
use LexiconSyntax\Lowering\LoweredGrammar;
use LexiconSyntax\Lowering\LoweredRule;
use LexiconSyntax\Lowering\Pattern\ActionPattern;
use LexiconSyntax\Lowering\Pattern\CapturePattern;
use LexiconSyntax\Lowering\Pattern\ChoicePattern;
use LexiconSyntax\Lowering\Pattern\LoweredPatternInterface;
use LexiconSyntax\Lowering\Pattern\ManyPattern;
use LexiconSyntax\Lowering\Pattern\OneOrMorePattern;
use LexiconSyntax\Lowering\Pattern\OptionalPattern;
use LexiconSyntax\Lowering\Pattern\ReferencePattern;
use LexiconSyntax\Lowering\Pattern\SequencePattern;
use LexiconSyntax\Lowering\ReferenceKind;
use LexiconSyntax\Planning\Parser\Expression\ArrayPrependExpression;
use LexiconSyntax\Planning\Parser\Expression\ArrayExpression;
use LexiconSyntax\Planning\Parser\Expression\ChoiceExpression;
use LexiconSyntax\Planning\Parser\Expression\ConstructNodeExpression;
use LexiconSyntax\Planning\Parser\Expression\FoldLeftExpression;
use LexiconSyntax\Planning\Parser\Expression\ManyExpression;
use LexiconSyntax\Planning\Parser\Expression\NullExpression;
use LexiconSyntax\Planning\Parser\Expression\OneOrMoreExpression;
use LexiconSyntax\Planning\Parser\Expression\OptionalExpression;
use LexiconSyntax\Planning\Parser\Expression\ParserExpressionInterface;
use LexiconSyntax\Planning\Parser\Expression\RuleCallExpression;
use LexiconSyntax\Planning\Parser\Expression\SequenceExpression;
use LexiconSyntax\Planning\Parser\Expression\SuccessExpression;
use LexiconSyntax\Planning\Parser\Expression\TokenMatchExpression;
use LexiconSyntax\Planning\Parser\Expression\VariableExpression;
use LexiconSyntax\Planning\Parser\Statement\AssignStatement;
use LexiconSyntax\Planning\Parser\Statement\ParserStatementInterface;
use LexiconSyntax\Planning\Parser\Statement\ReturnNullOnNullStatement;
use LexiconSyntax\Planning\Parser\Statement\SavePositionStatement;
use LexiconSyntax\Typing\AttributeTypeResolver;
use LexiconSyntax\Typing\RuleNameType;
use LexiconSyntax\Typing\SemanticTypeInterface;
use LexiconSyntax\Typing\TokenNameType;
use LexiconSyntax\Typing\UnionType;
use RuntimeException;

final readonly class ParserPlanner
{
    public static function plan(
        LoweredGrammar $grammar,
        string $parserName,
        string $tokenEnumName,
        ?GrammarIndex $index = null
    ): ParserPlan
    {
        if ($grammar->rules === []) {
            throw new RuntimeException('Cannot plan parser for grammar without lowered rules.');
        }

        $startRule = self::startRule($grammar);
        $methods = [];
        foreach ($grammar->rules as $rule) {
            $methods[] = self::method($rule, $index);
        }

        return new ParserPlan(
            self::identifier($parserName),
            self::identifier($tokenEnumName),
            self::methodName($startRule->name),
            $startRule->name,
            $methods
        );
    }

    private static function method(LoweredRule $rule, ?GrammarIndex $index): ParserMethodPlan
    {
        if ($rule->action === null) {
            return new ParserMethodPlan(
                self::methodName($rule->name),
                $rule->name,
                [],
                self::expression($rule->pattern, $index)
            );
        }

        $statements = [new SavePositionStatement()];
        $statements = [...$statements, ...self::statements($rule->pattern, null, $index)];

        $return = $rule->action instanceof ConstructNodeAction
            ? new ConstructNodeExpression($rule->action->nodeName, $rule->action->arguments)
            : new SuccessExpression();

        return new ParserMethodPlan(self::methodName($rule->name), $rule->name, $statements, $return);
    }

    /**
     * @return list<ParserStatementInterface>
     */
    private static function statements(LoweredPatternInterface $pattern, ?string $capture, ?GrammarIndex $index): array
    {
        if ($pattern instanceof SequencePattern) {
            $statements = [];
            foreach ($pattern->items as $item) {
                $statements = [...$statements, ...self::statements($item, null, $index)];
            }

            return $statements;
        }

        if ($pattern instanceof CapturePattern) {
            return self::statements($pattern->pattern, $pattern->name, $index);
        }

        $variable = $capture ?? 'result';

        $statements = [];
        if ($pattern instanceof OptionalPattern) {
            foreach (self::captures($pattern->pattern) as $optionalCapture) {
                $statements[] = new AssignStatement($optionalCapture, new NullExpression());
            }
        }

        $statements[] = new AssignStatement($variable, self::expression($pattern, $index));

        if (!self::canSucceedWithoutMatch($pattern)) {
            $statements[] = new ReturnNullOnNullStatement($variable);
        }

        return $statements;
    }

    private static function expression(LoweredPatternInterface $pattern, ?GrammarIndex $index): ParserExpressionInterface
    {
        if ($pattern instanceof ReferencePattern) {
            return match ($pattern->kind) {
                ReferenceKind::Token => new TokenMatchExpression($pattern->name),
                ReferenceKind::Rule => new RuleCallExpression($pattern->name, self::methodName($pattern->name)),
                ReferenceKind::Type => self::typeExpression($pattern->name, $index),
            };
        }

        if ($pattern instanceof ActionPattern) {
            if (!$pattern->action instanceof ConstructNodeAction) {
                throw new RuntimeException(sprintf('Action %s is not supported by parser planning yet.', $pattern->action::class));
            }

            return new SequenceExpression(
                [
                    new SavePositionStatement(),
                    ...self::statements($pattern->pattern, null, $index),
                ],
                new ConstructNodeExpression($pattern->action->nodeName, $pattern->action->arguments)
            );
        }

        if ($pattern instanceof SequencePattern) {
            return self::sequenceExpression($pattern, $index);
        }

        if ($pattern instanceof ChoicePattern) {
            $choices = [];
            foreach ($pattern->alternatives as $alternative) {
                $choices[] = self::expression($alternative, $index);
            }

            return new ChoiceExpression($choices);
        }

        if ($pattern instanceof OptionalPattern) {
            return new OptionalExpression(self::expression($pattern->pattern, $index));
        }

        if ($pattern instanceof ManyPattern) {
            return new ManyExpression(self::expression($pattern->pattern, $index));
        }

        if ($pattern instanceof OneOrMorePattern) {
            return new OneOrMoreExpression(self::expression($pattern->pattern, $index));
        }

        throw new RuntimeException(sprintf('Pattern %s is not supported by parser planning yet.', $pattern::class));
    }

    private static function sequenceExpression(SequencePattern $pattern, ?GrammarIndex $index): SequenceExpression
    {
        $statements = [new SavePositionStatement()];
        $variables = [];

        foreach ($pattern->items as $offset => $item) {
            $variable = $item instanceof CapturePattern ? $item->name : '__part' . $offset;
            $inner = $item instanceof CapturePattern ? $item->pattern : $item;

            $statements[] = new AssignStatement($variable, self::expression($inner, $index));
            if (!self::canSucceedWithoutMatch($inner)) {
                $statements[] = new ReturnNullOnNullStatement($variable);
            }

            $variables[$offset] = $variable;
        }

        return new SequenceExpression($statements, self::sequenceReturnExpression($pattern, $variables, $index));
    }

    /**
     * @param array<int, string> $variables
     */
    private static function sequenceReturnExpression(
        SequencePattern $pattern,
        array $variables,
        ?GrammarIndex $index
    ): ParserExpressionInterface
    {
        $list = self::separatedListVariables($pattern, $variables);
        if ($list !== null) {
            return new ArrayPrependExpression(
                new VariableExpression($list[0]),
                new VariableExpression($list[1])
            );
        }

        $fold = self::foldVariables($pattern, $variables, $index);
        if ($fold !== null) {
            return new FoldLeftExpression(
                new VariableExpression($fold[0]),
                new VariableExpression($fold[1]),
                'BinaryExpression'
            );
        }

        $pair = self::binaryTailPairVariables($pattern, $variables, $index);
        if ($pair !== null) {
            return new ArrayExpression([
                new VariableExpression($pair[0]),
                new VariableExpression($pair[1]),
            ]);
        }

        foreach ($pattern->items as $offset => $item) {
            if (($item instanceof ManyPattern || $item instanceof OneOrMorePattern || $item instanceof OptionalPattern)
                && isset($variables[0])
            ) {
                return new VariableExpression($variables[0]);
            }
        }

        for ($offset = count($pattern->items) - 1; $offset >= 0; $offset--) {
            if (self::isValuePattern($pattern->items[$offset])) {
                return new VariableExpression($variables[$offset]);
            }
        }

        return isset($variables[array_key_last($variables)])
            ? new VariableExpression($variables[array_key_last($variables)])
            : new SuccessExpression();
    }

    /**
     * @param array<int, string> $variables
     * @return array{0: string, 1: string}|null
     */
    private static function foldVariables(SequencePattern $pattern, array $variables, ?GrammarIndex $index): ?array
    {
        if ($index?->node('BinaryExpression') === null || count($pattern->items) !== 2 || !isset($variables[0], $variables[1])) {
            return null;
        }

        $head = self::unwrapCapture($pattern->items[0]);
        $tail = self::unwrapCapture($pattern->items[1]);
        if (!$tail instanceof ManyPattern) {
            return null;
        }

        $tailPattern = self::unwrapCapture($tail->pattern);
        if (!$tailPattern instanceof SequencePattern || count($tailPattern->items) !== 2) {
            return null;
        }

        $operator = self::unwrapCapture($tailPattern->items[0]);
        $right = self::unwrapCapture($tailPattern->items[1]);

        return self::isOperatorPattern($operator, $index) && self::sameReference($head, $right)
            ? [$variables[0], $variables[1]]
            : null;
    }

    /**
     * @param array<int, string> $variables
     * @return array{0: string, 1: string}|null
     */
    private static function binaryTailPairVariables(
        SequencePattern $pattern,
        array $variables,
        ?GrammarIndex $index
    ): ?array {
        if (count($pattern->items) !== 2 || !isset($variables[0], $variables[1])) {
            return null;
        }

        $operator = self::unwrapCapture($pattern->items[0]);
        $right = self::unwrapCapture($pattern->items[1]);

        return self::isOperatorPattern($operator, $index) && self::isRuleValuePattern($right)
            ? [$variables[0], $variables[1]]
            : null;
    }

    /**
     * @param array<int, string> $variables
     * @return array{0: string, 1: string}|null
     */
    private static function separatedListVariables(SequencePattern $pattern, array $variables): ?array
    {
        if (count($pattern->items) !== 2 || !isset($variables[0], $variables[1])) {
            return null;
        }

        $head = self::unwrapCapture($pattern->items[0]);
        $tail = self::unwrapCapture($pattern->items[1]);
        if (!$tail instanceof ManyPattern) {
            return null;
        }

        $tailPattern = self::unwrapCapture($tail->pattern);
        if (!$tailPattern instanceof SequencePattern || count($tailPattern->items) !== 2) {
            return null;
        }

        $separator = self::unwrapCapture($tailPattern->items[0]);
        if (!$separator instanceof ReferencePattern
            || $separator->kind !== ReferenceKind::Token
            || $separator->name !== 'Comma'
        ) {
            return null;
        }

        $tailItem = self::unwrapCapture($tailPattern->items[1]);

        return self::sameReference($head, $tailItem) ? [$variables[0], $variables[1]] : null;
    }

    private static function unwrapCapture(LoweredPatternInterface $pattern): LoweredPatternInterface
    {
        return $pattern instanceof CapturePattern ? $pattern->pattern : $pattern;
    }

    private static function sameReference(LoweredPatternInterface $left, LoweredPatternInterface $right): bool
    {
        return $left instanceof ReferencePattern
            && $right instanceof ReferencePattern
            && $left->name === $right->name
            && $left->kind === $right->kind;
    }

    private static function isOperatorPattern(LoweredPatternInterface $pattern, ?GrammarIndex $index): bool
    {
        if (!$pattern instanceof ReferencePattern) {
            return false;
        }

        if ($pattern->kind === ReferenceKind::Token) {
            return $pattern->name !== 'Comma';
        }

        return $pattern->kind === ReferenceKind::Type
            && $index !== null
            && self::typeContainsOnlyTokens($pattern->name, $index);
    }

    private static function typeContainsOnlyTokens(string $name, GrammarIndex $index): bool
    {
        $type = $index->type($name);
        if ($type === null) {
            return false;
        }

        $resolved = AttributeTypeResolver::resolveExpression($type->value, $index);
        if ($resolved instanceof TokenNameType) {
            return true;
        }

        if (!$resolved instanceof UnionType) {
            return false;
        }

        foreach ($resolved->types as $candidate) {
            if (!$candidate instanceof TokenNameType) {
                return false;
            }
        }

        return true;
    }

    private static function isRuleValuePattern(LoweredPatternInterface $pattern): bool
    {
        return $pattern instanceof ReferencePattern && $pattern->kind === ReferenceKind::Rule;
    }

    private static function isValuePattern(LoweredPatternInterface $pattern): bool
    {
        $pattern = self::unwrapCapture($pattern);

        return $pattern instanceof ReferencePattern
            || $pattern instanceof ActionPattern
            || $pattern instanceof ChoicePattern
            || $pattern instanceof OptionalPattern
            || $pattern instanceof ManyPattern
            || $pattern instanceof OneOrMorePattern;
    }

    private static function typeExpression(string $name, ?GrammarIndex $index): ParserExpressionInterface
    {
        if ($index === null) {
            throw new RuntimeException(sprintf("Type reference '%s' requires a grammar index for parser planning.", $name));
        }

        $declaration = $index->type($name);
        if ($declaration === null) {
            throw new RuntimeException(sprintf("Type reference '%s' cannot be resolved for parser planning.", $name));
        }

        return self::semanticTypeExpression(AttributeTypeResolver::resolveExpression($declaration->value, $index));
    }

    private static function semanticTypeExpression(SemanticTypeInterface $type): ParserExpressionInterface
    {
        if ($type instanceof TokenNameType) {
            return new TokenMatchExpression($type->name);
        }

        if ($type instanceof RuleNameType) {
            return new RuleCallExpression($type->name, self::methodName($type->name));
        }

        if ($type instanceof UnionType) {
            $choices = [];
            foreach ($type->types as $candidate) {
                $choices[] = self::semanticTypeExpression($candidate);
            }

            return new ChoiceExpression($choices);
        }

        throw new RuntimeException(sprintf("Type '%s' is not supported by parser planning.", $type->display()));
    }

    private static function canSucceedWithoutMatch(LoweredPatternInterface $pattern): bool
    {
        return $pattern instanceof OptionalPattern || $pattern instanceof ManyPattern;
    }

    /**
     * @return list<string>
     */
    private static function captures(LoweredPatternInterface $pattern): array
    {
        if ($pattern instanceof CapturePattern) {
            return [$pattern->name, ...self::captures($pattern->pattern)];
        }

        if ($pattern instanceof SequencePattern) {
            return array_values(array_unique(array_merge(...array_map(self::captures(...), $pattern->items))));
        }

        if ($pattern instanceof ChoicePattern) {
            return array_values(array_unique(array_merge(...array_map(self::captures(...), $pattern->alternatives))));
        }

        if ($pattern instanceof OptionalPattern || $pattern instanceof ManyPattern || $pattern instanceof OneOrMorePattern) {
            return self::captures($pattern->pattern);
        }

        if ($pattern instanceof ActionPattern) {
            return self::captures($pattern->pattern);
        }

        return [];
    }

    private static function startRule(LoweredGrammar $grammar): LoweredRule
    {
        foreach ($grammar->rules as $rule) {
            if ($rule->isStart) {
                return $rule;
            }
        }

        return $grammar->rules[0];
    }

    private static function methodName(string $name): string
    {
        return 'parse' . self::identifier($name);
    }

    private static function identifier(string $name): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new RuntimeException(sprintf("Invalid generated identifier '%s'.", $name));
        }

        return $name;
    }
}
