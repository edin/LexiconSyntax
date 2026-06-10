<?php

declare(strict_types=1);

namespace LexiconSyntax;

use LexiconSyntax\Ast\ActionCallNode;
use LexiconSyntax\Ast\ActionExpressionNode;
use LexiconSyntax\Ast\ActionValueNodeInterface;
use LexiconSyntax\Ast\ArrayLiteralNode;
use LexiconSyntax\Ast\AttributeArgumentNode;
use LexiconSyntax\Ast\AttributeDeclarationNode;
use LexiconSyntax\Ast\AttributeNode;
use LexiconSyntax\Ast\AttributeParameterNode;
use LexiconSyntax\Ast\AttributeTypeNode;
use LexiconSyntax\Ast\AttributeValueNodeInterface;
use LexiconSyntax\Ast\BooleanLiteralNode;
use LexiconSyntax\Ast\CharacterRangeExpressionNode;
use LexiconSyntax\Ast\ChoiceExpressionNode;
use LexiconSyntax\Ast\CustomMatcherExpressionNode;
use LexiconSyntax\Ast\ExpressionNode;
use LexiconSyntax\Ast\ExpressionNodeInterface;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\GroupExpressionNode;
use LexiconSyntax\Ast\IdentifierNode;
use LexiconSyntax\Ast\ImportDeclarationNode;
use LexiconSyntax\Ast\LabeledExpressionNode;
use LexiconSyntax\Ast\NodeDeclarationNode;
use LexiconSyntax\Ast\NumberLiteralNode;
use LexiconSyntax\Ast\QuantifiedExpressionNode;
use LexiconSyntax\Ast\ReferenceExpressionNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\SequenceExpressionNode;
use LexiconSyntax\Ast\StringLiteralNode;
use LexiconSyntax\Ast\StringLiteralExpressionNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\Ast\TypeDeclarationNode;
use LexiconSyntax\Ast\TypeExpressionNode;

final readonly class GrammarPrinter
{
    public static function format(GrammarDocumentNode $document): string
    {
        $lines = [];

        foreach ($document->imports as $import) {
            $lines[] = self::importDeclaration($import);
        }

        foreach ($document->typeDeclarations as $declaration) {
            $lines[] = self::typeDeclaration($declaration);
        }

        foreach ($document->attributeDeclarations as $declaration) {
            $lines[] = self::attributeDeclaration($declaration);
        }

        foreach ($document->nodeDeclarations as $declaration) {
            $lines[] = self::nodeDeclaration($declaration);
        }

        foreach ($document->declarations as $declaration) {
            foreach ($declaration->attributes() as $attribute) {
                $lines[] = self::attribute($attribute);
            }

            $category = $declaration instanceof TokenDeclarationNode && $declaration->categoryName() !== null
                ? ' ' . $declaration->categoryName()
                : '';
            $returnType = $declaration instanceof RuleDeclarationNode && $declaration->returnType !== null
                ? ' : ' . self::typeExpression($declaration->returnType)
                : '';

            $expression = $declaration->expression();
            $lines[] = $expression === null
                ? sprintf(
                    '%s%s %s;',
                    $declaration->kind(),
                    $category,
                    $declaration->nameToken()->value
                )
                : sprintf(
                    '%s%s %s%s ::= %s;',
                    $declaration->kind(),
                    $category,
                    $declaration->nameToken()->value,
                    $returnType,
                    self::expression($expression)
                );
        }

        return implode(PHP_EOL, $lines);
    }

    public static function importDeclaration(ImportDeclarationNode $import): string
    {
        return sprintf('import %s;', $import->path->token->value);
    }

    public static function typeDeclaration(TypeDeclarationNode $declaration): string
    {
        return sprintf(
            'type %s = %s;',
            $declaration->name->token->value,
            self::typeExpression($declaration->value)
        );
    }

    public static function nodeDeclaration(NodeDeclarationNode $declaration): string
    {
        $parent = $declaration->parentName() === null ? '' : ' : ' . $declaration->parentName();

        if ($declaration->fields === []) {
            return sprintf('node %s%s;', $declaration->name->token->value, $parent);
        }

        return sprintf(
            'node %s%s(%s);',
            $declaration->name->token->value,
            $parent,
            implode(', ', array_map(self::attributeParameter(...), $declaration->fields))
        );
    }

    public static function attributeDeclaration(AttributeDeclarationNode $declaration): string
    {
        if ($declaration->parameters === []) {
            return sprintf(
                'attribute %s %s;',
                $declaration->targetName(),
                $declaration->name->token->value
            );
        }

        return sprintf(
            'attribute %s %s(%s);',
            $declaration->targetName(),
            $declaration->name->token->value,
            implode(', ', array_map(self::attributeParameter(...), $declaration->parameters))
        );
    }

    private static function attributeParameter(AttributeParameterNode $parameter): string
    {
        return sprintf('%s: %s', $parameter->name->token->value, self::attributeType($parameter->type));
    }

    private static function attributeType(AttributeTypeNode $type): string
    {
        $base = $type->name->token->value;
        if ($base === 'enum') {
            $base .= '[' . implode(', ', array_map(
                fn ($value): string => $value->token->value,
                $type->enumValues
            )) . ']';
        }

        return $type->isArray() ? $base . '[]' : $base;
    }

    private static function typeExpression(TypeExpressionNode $type): string
    {
        return implode(' | ', array_map(self::attributeType(...), $type->alternatives));
    }

    public static function attribute(AttributeNode $attribute): string
    {
        if ($attribute->arguments === []) {
            return sprintf('#[%s]', $attribute->name->token->value);
        }

        return sprintf(
            '#[%s(%s)]',
            $attribute->name->token->value,
            implode(', ', array_map(self::attributeArgument(...), $attribute->arguments))
        );
    }

    private static function attributeArgument(AttributeArgumentNode $argument): string
    {
        $value = self::attributeValue($argument->value);

        return $argument->name === null
            ? $value
            : sprintf('%s: %s', $argument->name->token->value, $value);
    }

    private static function attributeValue(AttributeValueNodeInterface $value): string
    {
        if ($value instanceof ArrayLiteralNode) {
            return '[' . implode(', ', array_map(self::attributeValue(...), $value->items)) . ']';
        }

        if ($value instanceof StringLiteralNode) {
            return $value->token->value;
        }

        if ($value instanceof NumberLiteralNode) {
            return $value->token->value;
        }

        if ($value instanceof BooleanLiteralNode) {
            return $value->token->value;
        }

        if ($value instanceof IdentifierNode) {
            return $value->token->value;
        }

        return '<unknown>';
    }

    public static function expression(ExpressionNodeInterface $expression): string
    {
        if ($expression instanceof ExpressionNode) {
            return self::expression($expression->inner);
        }

        if ($expression instanceof ActionExpressionNode) {
            return self::expression($expression->pattern) . ' => ' . self::actionValue($expression->action);
        }

        if ($expression instanceof ChoiceExpressionNode) {
            return implode(' | ', array_map(self::parenthesizeSequence(...), $expression->alternatives));
        }

        if ($expression instanceof SequenceExpressionNode) {
            return implode(' ', array_map(self::parenthesizeChoice(...), $expression->items));
        }

        if ($expression instanceof QuantifiedExpressionNode) {
            return self::parenthesizeForQuantifier($expression->expression) . $expression->quantifier->value;
        }

        if ($expression instanceof LabeledExpressionNode) {
            return $expression->label->token->value . ': ' . self::expression($expression->expression);
        }

        if ($expression instanceof GroupExpressionNode) {
            return '(' . self::expression($expression->expression) . ')';
        }

        if ($expression instanceof CharacterRangeExpressionNode) {
            return $expression->start->token->value . ' .. ' . $expression->end->token->value;
        }

        if ($expression instanceof CustomMatcherExpressionNode) {
            return '<' . $expression->name->token->value . '>';
        }

        if ($expression instanceof StringLiteralExpressionNode) {
            return $expression->literal->token->value;
        }

        if ($expression instanceof ReferenceExpressionNode) {
            return $expression->token->value;
        }

        return '<unknown>';
    }

    public static function actionValue(ActionValueNodeInterface $action): string
    {
        if ($action instanceof ActionCallNode) {
            return sprintf(
                '%s(%s)',
                $action->name->token->value,
                implode(', ', array_map(self::actionValue(...), $action->arguments))
            );
        }

        if ($action instanceof IdentifierNode) {
            return $action->token->value;
        }

        if ($action instanceof StringLiteralNode) {
            return $action->token->value;
        }

        if ($action instanceof NumberLiteralNode || $action instanceof BooleanLiteralNode) {
            return $action->token->value;
        }

        return '<unknown>';
    }

    private static function parenthesizeSequence(ExpressionNodeInterface $expression): string
    {
        return $expression instanceof SequenceExpressionNode
            || $expression instanceof ActionExpressionNode
            ? '(' . self::expression($expression) . ')'
            : self::expression($expression);
    }

    private static function parenthesizeChoice(ExpressionNodeInterface $expression): string
    {
        return $expression instanceof ChoiceExpressionNode
            || $expression instanceof ActionExpressionNode
            ? '(' . self::expression($expression) . ')'
            : self::expression($expression);
    }

    private static function parenthesizeForQuantifier(ExpressionNodeInterface $expression): string
    {
        return $expression instanceof ReferenceExpressionNode
            || $expression instanceof StringLiteralExpressionNode
            || $expression instanceof CharacterRangeExpressionNode
            || $expression instanceof CustomMatcherExpressionNode
            || $expression instanceof LabeledExpressionNode
            || $expression instanceof GroupExpressionNode
                ? self::expression($expression)
                : '(' . self::expression($expression) . ')';
    }
}
