<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering;

use LexiconSyntax\Ast\ActionCallNode;
use LexiconSyntax\Ast\ActionExpressionNode;
use LexiconSyntax\Ast\ChoiceExpressionNode;
use LexiconSyntax\Ast\ExpressionNode;
use LexiconSyntax\Ast\ExpressionNodeInterface;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\GroupExpressionNode;
use LexiconSyntax\Ast\IdentifierNode;
use LexiconSyntax\Ast\LabeledExpressionNode;
use LexiconSyntax\Ast\QuantifiedExpressionNode;
use LexiconSyntax\Ast\ReferenceExpressionNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\SequenceExpressionNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\GrammarTokenType;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Lowering\Action\ConstructNodeAction;
use LexiconSyntax\Lowering\Action\LoweredActionInterface;
use LexiconSyntax\Lowering\Pattern\ActionPattern;
use LexiconSyntax\Lowering\Pattern\CapturePattern;
use LexiconSyntax\Lowering\Pattern\ChoicePattern;
use LexiconSyntax\Lowering\Pattern\LoweredPatternInterface;
use LexiconSyntax\Lowering\Pattern\ManyPattern;
use LexiconSyntax\Lowering\Pattern\OneOrMorePattern;
use LexiconSyntax\Lowering\Pattern\OptionalPattern;
use LexiconSyntax\Lowering\Pattern\ReferencePattern;
use LexiconSyntax\Lowering\Pattern\SequencePattern;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class GrammarLowerer
{
    public static function lower(GrammarDocumentNode $document, GrammarIndex $index): LoweredGrammar
    {
        return self::lowerWithDiagnostics($document, $index)->grammar;
    }

    public static function lowerWithDiagnostics(GrammarDocumentNode $document, GrammarIndex $index): LoweringResult
    {
        $rules = [];
        $diagnostics = [];
        foreach ($document->declarations as $declaration) {
            if (!$declaration instanceof RuleDeclarationNode) {
                continue;
            }

            $rule = self::lowerRule($declaration, $index, $diagnostics);
            if ($rule !== null) {
                $rules[] = $rule;
            }
        }

        return new LoweringResult(new LoweredGrammar($rules), $diagnostics);
    }

    /**
     * @param list<GrammarDiagnostic> $diagnostics
     */
    private static function lowerRule(RuleDeclarationNode $declaration, GrammarIndex $index, array &$diagnostics): ?LoweredRule
    {
        $expression = self::unwrap($declaration->expression());
        $action = null;
        if ($expression instanceof ActionExpressionNode) {
            $action = self::lowerAction($expression->action, $index);
            if ($action === null) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Rule '%s' uses an action that cannot be lowered.", $declaration->nameToken()->value),
                    $expression->arrow
                );

                return null;
            }

            $expression = self::unwrap($expression->pattern);
        }

        $pattern = self::lowerPattern($expression, $index, $diagnostics);
        if ($pattern === null) {
            $diagnostics[] = new GrammarDiagnostic(
                sprintf("Rule '%s' uses a pattern that cannot be lowered.", $declaration->nameToken()->value),
                $declaration->nameToken()
            );

            return null;
        }

        return new LoweredRule(
            $declaration->nameToken()->value,
            $pattern,
            $action,
            self::hasAttribute($declaration, 'start')
        );
    }

    private static function lowerAction(\LexiconSyntax\Ast\ActionValueNodeInterface $action, GrammarIndex $index): ?LoweredActionInterface
    {
        if (!$action instanceof ActionCallNode) {
            return null;
        }

        if ($index->node($action->name->token->value) === null) {
            return null;
        }

        $arguments = [];
        foreach ($action->arguments as $argument) {
            if (!$argument instanceof IdentifierNode) {
                return null;
            }

            $arguments[] = $argument->token->value;
        }

        return new ConstructNodeAction($action->name->token->value, $arguments);
    }

    /**
     * @param list<GrammarDiagnostic> $diagnostics
     */
    private static function lowerPattern(
        ExpressionNodeInterface $expression,
        GrammarIndex $index,
        array &$diagnostics
    ): ?LoweredPatternInterface
    {
        $expression = self::unwrap($expression);

        if ($expression instanceof ActionExpressionNode) {
            $action = self::lowerAction($expression->action, $index);
            if ($action === null) {
                $diagnostics[] = new GrammarDiagnostic('Action alternative cannot be lowered.', $expression->arrow);

                return null;
            }

            $pattern = self::lowerPattern($expression->pattern, $index, $diagnostics);

            return $pattern === null ? null : new ActionPattern($pattern, $action);
        }

        if ($expression instanceof SequenceExpressionNode) {
            $items = [];
            foreach ($expression->items as $item) {
                $lowered = self::lowerPattern($item, $index, $diagnostics);
                if ($lowered === null) {
                    return null;
                }

                $items[] = $lowered;
            }

            return new SequencePattern($items);
        }

        if ($expression instanceof ChoiceExpressionNode) {
            $alternatives = [];
            foreach ($expression->alternatives as $alternative) {
                $lowered = self::lowerPattern($alternative, $index, $diagnostics);
                if ($lowered === null) {
                    return null;
                }

                $alternatives[] = $lowered;
            }

            return new ChoicePattern($alternatives);
        }

        if ($expression instanceof GroupExpressionNode) {
            return self::lowerPattern($expression->expression, $index, $diagnostics);
        }

        if ($expression instanceof QuantifiedExpressionNode) {
            $pattern = self::lowerPattern($expression->expression, $index, $diagnostics);
            if ($pattern === null) {
                return null;
            }

            return match ($expression->quantifier->type) {
                GrammarTokenType::Question => new OptionalPattern($pattern),
                GrammarTokenType::Star => new ManyPattern($pattern),
                GrammarTokenType::Plus => new OneOrMorePattern($pattern),
                default => null,
            };
        }

        if ($expression instanceof LabeledExpressionNode) {
            $pattern = self::lowerPattern($expression->expression, $index, $diagnostics);

            return $pattern === null
                ? null
                : new CapturePattern($expression->label->token->value, $pattern);
        }

        if ($expression instanceof ReferenceExpressionNode) {
            $kind = self::referenceKind($expression->token->value, $index);

            return $kind === null
                ? self::unsupportedReference($expression, $diagnostics)
                : new ReferencePattern($expression->token->value, $kind);
        }

        $diagnostics[] = new GrammarDiagnostic(sprintf('Pattern %s cannot be lowered yet.', $expression::class));

        return null;
    }

    /**
     * @param list<GrammarDiagnostic> $diagnostics
     */
    private static function unsupportedReference(ReferenceExpressionNode $expression, array &$diagnostics): null
    {
        $diagnostics[] = new GrammarDiagnostic(
            sprintf("Reference '%s' cannot be resolved for lowering.", $expression->token->value),
            $expression->token
        );

        return null;
    }

    private static function referenceKind(string $name, GrammarIndex $index): ?ReferenceKind
    {
        $declaration = $index->declaration($name);
        if ($declaration instanceof TokenDeclarationNode) {
            return ReferenceKind::Token;
        }

        if ($declaration instanceof RuleDeclarationNode) {
            return ReferenceKind::Rule;
        }

        if ($index->type($name) !== null) {
            return ReferenceKind::Type;
        }

        return null;
    }

    private static function unwrap(ExpressionNodeInterface $expression): ExpressionNodeInterface
    {
        return $expression instanceof ExpressionNode ? $expression->inner : $expression;
    }

    private static function hasAttribute(RuleDeclarationNode $declaration, string $name): bool
    {
        foreach ($declaration->attributes as $attribute) {
            if ($attribute->name->token->value === $name) {
                return true;
            }
        }

        return false;
    }
}
