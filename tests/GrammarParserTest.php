<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use Lexicon\Parser\Debug\AstPrinter;
use LexiconSyntax\Ast\ActionCallNode;
use LexiconSyntax\Ast\ActionExpressionNode;
use LexiconSyntax\Ast\ArrayLiteralNode;
use LexiconSyntax\Ast\CharacterRangeExpressionNode;
use LexiconSyntax\Ast\ChoiceExpressionNode;
use LexiconSyntax\Ast\CustomMatcherExpressionNode;
use LexiconSyntax\Ast\ExpressionNode;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\IdentifierNode;
use LexiconSyntax\Ast\ImportDeclarationNode;
use LexiconSyntax\Ast\LabeledExpressionNode;
use LexiconSyntax\Ast\NodeDeclarationNode;
use LexiconSyntax\Ast\NumberLiteralNode;
use LexiconSyntax\Ast\QuantifiedExpressionNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\SequenceExpressionNode;
use LexiconSyntax\Ast\StringLiteralNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\Ast\TypeDeclarationNode;
use LexiconSyntax\GrammarParser;
use PHPUnit\Framework\TestCase;

final class GrammarParserTest extends TestCase
{
    public function testParserReadsTokenAndRuleDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token Digit ::= '0' .. '9';
token Identifier ::= Digit+;
rule Expression ::= Term ((Plus | Minus) Term)*;
GRAMMAR);

        self::assertInstanceOf(GrammarDocumentNode::class, $document);
        self::assertCount(3, $document->declarations);
        self::assertInstanceOf(TokenDeclarationNode::class, $document->declarations[0]);
        self::assertSame('Digit', $document->declarations[0]->nameToken()->value);
        $digitExpression = $document->declarations[0]->expression();
        $identifierExpression = $document->declarations[1]->expression();
        self::assertInstanceOf(ExpressionNode::class, $digitExpression);
        self::assertInstanceOf(ExpressionNode::class, $identifierExpression);
        self::assertInstanceOf(CharacterRangeExpressionNode::class, $digitExpression->inner);
        self::assertInstanceOf(QuantifiedExpressionNode::class, $identifierExpression->inner);
        self::assertInstanceOf(RuleDeclarationNode::class, $document->declarations[2]);
        $ruleExpression = $document->declarations[2]->expression();
        self::assertInstanceOf(ExpressionNode::class, $ruleExpression);
        self::assertInstanceOf(SequenceExpressionNode::class, $ruleExpression->inner);
    }

    public function testParserPreservesChoiceAndGroupingShapeForAstPrinter(): void
    {
        $document = GrammarParser::parse("rule Factor ::= Number | GroupedExpression;\n");
        $wrapper = $document->declarations[0]->expression();
        self::assertInstanceOf(ExpressionNode::class, $wrapper);
        $expression = $wrapper->inner;

        self::assertInstanceOf(ChoiceExpressionNode::class, $expression);
        self::assertStringContainsString('ChoiceExpressionNode', AstPrinter::format($document));
        self::assertStringContainsString('ReferenceExpressionNode "Number"', AstPrinter::format($document));
    }

    public function testParserReadsCustomMatcherExpressions(): void
    {
        $document = GrammarParser::parse("token Comment ::= <CommentMatcher>;\n");
        $wrapper = $document->declarations[0]->expression();
        self::assertInstanceOf(ExpressionNode::class, $wrapper);
        $expression = $wrapper->inner;

        self::assertInstanceOf(CustomMatcherExpressionNode::class, $expression);
        self::assertSame('CommentMatcher', $expression->name->token->value);
        self::assertStringContainsString('CustomMatcherExpressionNode', AstPrinter::format($document));
    }

    public function testParserReadsTokenCategoriesAndDoubleQuotedLiterals(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token keyword If ::= "if";
token symbol Plus ::= "+";
GRAMMAR);

        self::assertInstanceOf(TokenDeclarationNode::class, $document->declarations[0]);
        self::assertSame('keyword', $document->declarations[0]->categoryName());
        self::assertSame('If', $document->declarations[0]->nameToken()->value);
        self::assertInstanceOf(TokenDeclarationNode::class, $document->declarations[1]);
        self::assertSame('symbol', $document->declarations[1]->categoryName());
        self::assertSame('Plus', $document->declarations[1]->nameToken()->value);
    }

    public function testParserKeepsPlainTokenDeclarationsWithoutCategories(): void
    {
        $document = GrammarParser::parse("token Identifier ::= Letter+;\n");

        self::assertInstanceOf(TokenDeclarationNode::class, $document->declarations[0]);
        self::assertNull($document->declarations[0]->categoryName());
        self::assertSame('Identifier', $document->declarations[0]->nameToken()->value);
    }

    public function testParserReadsAssignmentlessTokenDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token eof EndOfFile;
token unknown Unknown;
GRAMMAR);

        self::assertInstanceOf(TokenDeclarationNode::class, $document->declarations[0]);
        self::assertSame('eof', $document->declarations[0]->categoryName());
        self::assertSame('EndOfFile', $document->declarations[0]->nameToken()->value);
        self::assertNull($document->declarations[0]->expression());
        self::assertInstanceOf(TokenDeclarationNode::class, $document->declarations[1]);
        self::assertSame('unknown', $document->declarations[1]->categoryName());
        self::assertNull($document->declarations[1]->expression());
    }

    public function testParserReadsLabeledSequenceParts(): void
    {
        $document = GrammarParser::parse("rule Binary ::= left: Term op: Plus right: Term;\n");
        $wrapper = $document->declarations[0]->expression();
        self::assertInstanceOf(ExpressionNode::class, $wrapper);
        self::assertInstanceOf(SequenceExpressionNode::class, $wrapper->inner);

        $left = $wrapper->inner->items[0];
        $op = $wrapper->inner->items[1];
        $right = $wrapper->inner->items[2];
        self::assertInstanceOf(LabeledExpressionNode::class, $left);
        self::assertSame('left', $left->label->token->value);
        self::assertInstanceOf(LabeledExpressionNode::class, $op);
        self::assertSame('op', $op->label->token->value);
        self::assertInstanceOf(LabeledExpressionNode::class, $right);
        self::assertSame('right', $right->label->token->value);
    }

    public function testParserReadsRuleReturnTypeAnnotations(): void
    {
        $document = GrammarParser::parse("rule Additive : Expression ::= Multiplicative (AdditiveOperator Multiplicative)*;\n");

        self::assertInstanceOf(RuleDeclarationNode::class, $document->declarations[0]);
        self::assertNotNull($document->declarations[0]->returnType);
        self::assertSame('Expression', $document->declarations[0]->returnType->alternatives[0]->name->token->value);
    }

    public function testParserReadsLabelsOnGroupedQuantifiedExpressions(): void
    {
        $document = GrammarParser::parse("rule Expr ::= rest: ((Plus | Minus) Term)*;\n");
        $wrapper = $document->declarations[0]->expression();
        self::assertInstanceOf(ExpressionNode::class, $wrapper);
        self::assertInstanceOf(LabeledExpressionNode::class, $wrapper->inner);
        self::assertSame('rest', $wrapper->inner->label->token->value);
        self::assertInstanceOf(QuantifiedExpressionNode::class, $wrapper->inner->expression);
    }

    public function testParserReadsRuleActions(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node BinaryExpression(op: token, left: rule, right: rule);
rule Binary ::= left: Term op: Plus right: Term => BinaryExpression(op, left, right);
GRAMMAR);
        $wrapper = $document->declarations[0]->expression();
        self::assertInstanceOf(ExpressionNode::class, $wrapper);
        self::assertInstanceOf(ActionExpressionNode::class, $wrapper->inner);
        self::assertInstanceOf(SequenceExpressionNode::class, $wrapper->inner->pattern);
        self::assertInstanceOf(ActionCallNode::class, $wrapper->inner->action);
        self::assertSame('BinaryExpression', $wrapper->inner->action->name->token->value);
        self::assertCount(3, $wrapper->inner->action->arguments);
    }

    public function testParserReadsActionsOnChoiceAlternatives(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
rule Factor ::= number: Number => NumberLiteral(number) | group: GroupedExpression => group;
GRAMMAR);
        $wrapper = $document->declarations[0]->expression();
        self::assertInstanceOf(ExpressionNode::class, $wrapper);
        self::assertInstanceOf(ChoiceExpressionNode::class, $wrapper->inner);
        self::assertInstanceOf(ActionExpressionNode::class, $wrapper->inner->alternatives[0]);
        self::assertInstanceOf(ActionExpressionNode::class, $wrapper->inner->alternatives[1]);
        self::assertInstanceOf(IdentifierNode::class, $wrapper->inner->alternatives[1]->action);
        self::assertSame('group', $wrapper->inner->alternatives[1]->action->token->value);
    }

    public function testParserReadsGenericAttributesOnDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
#[fold(operators: [Plus, Minus], associativity: left, precedence: 10, label: 'math')]
rule Expression ::= Number;
GRAMMAR);

        $attributes = $document->declarations[0]->attributes();

        self::assertCount(1, $attributes);
        self::assertSame('fold', $attributes[0]->name->token->value);
        self::assertCount(4, $attributes[0]->arguments);
        self::assertSame('operators', $attributes[0]->arguments[0]->name?->token->value);
        self::assertInstanceOf(ArrayLiteralNode::class, $attributes[0]->arguments[0]->value);
        self::assertSame('associativity', $attributes[0]->arguments[1]->name?->token->value);
        self::assertInstanceOf(IdentifierNode::class, $attributes[0]->arguments[1]->value);
        self::assertInstanceOf(NumberLiteralNode::class, $attributes[0]->arguments[2]->value);
        self::assertInstanceOf(StringLiteralNode::class, $attributes[0]->arguments[3]->value);
    }

    public function testParserIgnoresLineAndBlockComments(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
// digits are reusable token pieces
token Digit ::= '0' .. '9';

/*
 * parser entry shape
 */
#[start] // attach metadata to the next rule
rule Expression ::= Digit+; /* trailing comment */
GRAMMAR);

        self::assertCount(2, $document->declarations);
        self::assertSame('Digit', $document->declarations[0]->nameToken()->value);
        self::assertSame('Expression', $document->declarations[1]->nameToken()->value);
        self::assertSame('start', $document->declarations[1]->attributes()[0]->name->token->value);
    }

    public function testParserReadsAttributeDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
attribute rule recover(strategy: enum[panic, synchronize], tokens: identifier[], enabled: bool);
#[recover(strategy: synchronize, tokens: [Semicolon, CloseBrace], enabled: true)]
rule Statement ::= Block;
GRAMMAR);

        self::assertCount(1, $document->attributeDeclarations);
        self::assertSame('rule', $document->attributeDeclarations[0]->targetName());
        self::assertSame('recover', $document->attributeDeclarations[0]->name->token->value);
        self::assertCount(3, $document->attributeDeclarations[0]->parameters);
        self::assertSame('enum', $document->attributeDeclarations[0]->parameters[0]->type->name->token->value);
        self::assertSame('synchronize', $document->attributeDeclarations[0]->parameters[0]->type->enumValues[1]->token->value);
        self::assertTrue($document->attributeDeclarations[0]->parameters[1]->type->isArray());
        self::assertSame('bool', $document->attributeDeclarations[0]->parameters[2]->type->name->token->value);
    }

    public function testParserReadsNodeDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node BinaryExpression(op: token, left: rule, right: rule);
GRAMMAR);

        self::assertCount(1, $document->nodeDeclarations);
        self::assertInstanceOf(NodeDeclarationNode::class, $document->nodeDeclarations[0]);
        self::assertSame('BinaryExpression', $document->nodeDeclarations[0]->name->token->value);
        self::assertCount(3, $document->nodeDeclarations[0]->fields);
        self::assertSame('op', $document->nodeDeclarations[0]->fields[0]->name->token->value);
        self::assertSame('token', $document->nodeDeclarations[0]->fields[0]->type->name->token->value);
    }

    public function testParserReadsNodeHierarchyDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node Expression;
node BinaryExpression : Expression(op: token, left: rule, right: rule);
GRAMMAR);

        self::assertCount(2, $document->nodeDeclarations);
        self::assertSame('Expression', $document->nodeDeclarations[1]->parentName());
        self::assertCount(3, $document->nodeDeclarations[1]->fields);
    }

    public function testParserReadsTypeDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
type BinaryOperator = Plus | Minus | Star | Slash;
type AttributeValue = string | number | bool | identifier[];
GRAMMAR);

        self::assertCount(2, $document->typeDeclarations);
        self::assertInstanceOf(TypeDeclarationNode::class, $document->typeDeclarations[0]);
        self::assertSame('BinaryOperator', $document->typeDeclarations[0]->name->token->value);
        self::assertCount(4, $document->typeDeclarations[0]->value->alternatives);
        self::assertSame('Plus', $document->typeDeclarations[0]->value->alternatives[0]->name->token->value);
        self::assertSame('AttributeValue', $document->typeDeclarations[1]->name->token->value);
        self::assertTrue($document->typeDeclarations[1]->value->alternatives[3]->isArray());
    }

    public function testParserReadsImportDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
import "tokens.lxs";
token Number ::= Digit+;
GRAMMAR);

        self::assertCount(1, $document->imports);
        self::assertInstanceOf(ImportDeclarationNode::class, $document->imports[0]);
        self::assertSame('tokens.lxs', $document->imports[0]->pathValue());
    }
}
