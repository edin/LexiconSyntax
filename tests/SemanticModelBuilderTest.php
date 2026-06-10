<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\GrammarParser;
use LexiconSyntax\Model\SemanticModelBuilder;
use LexiconSyntax\Typing\ArrayType;
use LexiconSyntax\Typing\EnumType;
use LexiconSyntax\Typing\NodeNameType;
use LexiconSyntax\Typing\UnionType;
use PHPUnit\Framework\TestCase;

final class SemanticModelBuilderTest extends TestCase
{
    public function testBuilderCreatesSemanticModelForDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
type Associativity = enum[left, right];
type BinaryOperator = Plus | Minus;
attribute rule fold(operators: BinaryOperator[], associativity: Associativity);
node BinaryExpression(left: rule, op: BinaryOperator, right: rule);
token symbol Plus ::= '+';
token symbol Minus ::= '-';
token Number ::= '0';
#[fold(operators: [Plus, Minus], associativity: left)]
rule Expression ::= Number (BinaryOperator Number)*;
GRAMMAR);

        $model = SemanticModelBuilder::build($document);

        self::assertArrayHasKey('Plus', $model->tokens);
        self::assertSame('symbol', $model->tokens['Plus']->category);
        self::assertSame(['Number', 'BinaryOperator'], $model->rules['Expression']->references);
        self::assertTrue(isset($model->rules['Expression']->metadata->fold));

        self::assertArrayHasKey('BinaryOperator', $model->types);
        self::assertInstanceOf(UnionType::class, $model->types['BinaryOperator']->resolvedType);
        self::assertInstanceOf(EnumType::class, $model->types['Associativity']->resolvedType);

        self::assertSame('BinaryExpression', $model->nodes['BinaryExpression']->name);
        self::assertSame('op', $model->nodes['BinaryExpression']->fields[1]->name);
        self::assertInstanceOf(UnionType::class, $model->nodes['BinaryExpression']->fields[1]->type);

        $schema = $model->attributes['rule:fold'];
        self::assertSame('fold', $schema->name);
        self::assertInstanceOf(ArrayType::class, $schema->parameters[0]->type);
        self::assertInstanceOf(EnumType::class, $schema->parameters[1]->type);
    }

    public function testBuilderInfersRuleReturnTypeAndNarrowsToCommonNodeParent(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node Type;
node TypeName : Type(name: token);
node StructTypeName : Type(name: token);
token keyword Int ::= 'int';
token keyword Struct ::= 'struct';
token Identifier ::= <IdentifierMatcher>;
rule Type ::= name: Int => TypeName(name) | Struct name: Identifier => StructTypeName(name);
GRAMMAR);

        $model = SemanticModelBuilder::build($document);

        self::assertSame('Type', $model->nodes['TypeName']->parentName);
        self::assertInstanceOf(NodeNameType::class, $model->rules['Type']->returnType);
        self::assertSame('Type', $model->rules['Type']->returnType->name);
    }

    public function testBuilderUsesDeclaredRuleReturnType(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node Expression;
node NumberLiteral : Expression(value: token);
token Number ::= '0';
rule Primary : Expression ::= value: Number => NumberLiteral(value);
GRAMMAR);

        $model = SemanticModelBuilder::build($document);

        self::assertInstanceOf(NodeNameType::class, $model->rules['Primary']->returnType);
        self::assertSame('Expression', $model->rules['Primary']->returnType->name);
    }
}
