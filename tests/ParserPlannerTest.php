<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\GrammarIndex;
use LexiconSyntax\GrammarParser;
use LexiconSyntax\Lowering\GrammarLowerer;
use LexiconSyntax\Planning\Parser\Expression\ChoiceExpression;
use LexiconSyntax\Planning\Parser\Expression\ConstructNodeExpression;
use LexiconSyntax\Planning\Parser\Expression\ManyExpression;
use LexiconSyntax\Planning\Parser\Expression\OneOrMoreExpression;
use LexiconSyntax\Planning\Parser\Expression\OptionalExpression;
use LexiconSyntax\Planning\Parser\ParserPlanner;
use LexiconSyntax\Planning\Parser\Expression\SequenceExpression;
use LexiconSyntax\Planning\Parser\Statement\AssignStatement;
use LexiconSyntax\Planning\Parser\Statement\ReturnNullOnNullStatement;
use LexiconSyntax\Planning\Parser\Statement\SavePositionStatement;
use PHPUnit\Framework\TestCase;

final class ParserPlannerTest extends TestCase
{
    public function testPlannerBuildsLanguageNeutralParserPlan(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node Parameter(valueType: rule, name: token);
token keyword Int ::= 'int';
token keyword Float ::= 'float';
token Identifier ::= <IdentifierMatcher>;
rule Type ::= Int | Float;
#[start]
rule Parameter ::= valueType: Type name: Identifier => Parameter(valueType, name);
GRAMMAR);

        $lowered = GrammarLowerer::lower($document, GrammarIndex::from($document));
        $plan = ParserPlanner::plan($lowered, 'DemoParser', 'DemoTokenType', GrammarIndex::from($document));

        self::assertSame('DemoParser', $plan->name);
        self::assertSame('DemoTokenType', $plan->tokenEnumName);
        self::assertSame('parseParameter', $plan->startMethodName);
        self::assertSame('Parameter', $plan->startRuleName);
        self::assertCount(2, $plan->methods);

        self::assertSame('parseType', $plan->methods[0]->name);
        self::assertSame([], $plan->methods[0]->statements);
        self::assertInstanceOf(ChoiceExpression::class, $plan->methods[0]->returnExpression);

        self::assertSame('parseParameter', $plan->methods[1]->name);
        self::assertInstanceOf(SavePositionStatement::class, $plan->methods[1]->statements[0]);
        self::assertInstanceOf(AssignStatement::class, $plan->methods[1]->statements[1]);
        self::assertSame('valueType', $plan->methods[1]->statements[1]->variable);
        self::assertInstanceOf(ReturnNullOnNullStatement::class, $plan->methods[1]->statements[2]);
        self::assertInstanceOf(AssignStatement::class, $plan->methods[1]->statements[3]);
        self::assertSame('name', $plan->methods[1]->statements[3]->variable);
        self::assertInstanceOf(ReturnNullOnNullStatement::class, $plan->methods[1]->statements[4]);
        self::assertInstanceOf(ConstructNodeExpression::class, $plan->methods[1]->returnExpression);
    }

    public function testPlannerBuildsQuantifierExpressions(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node Items(maybe: token, many: token, required: token);
token Identifier ::= <IdentifierMatcher>;
#[start]
rule Items ::= maybe: Identifier? many: Identifier* required: Identifier+ => Items(maybe, many, required);
GRAMMAR);

        $lowered = GrammarLowerer::lower($document, GrammarIndex::from($document));
        $plan = ParserPlanner::plan($lowered, 'DemoParser', 'DemoTokenType', GrammarIndex::from($document));
        $method = $plan->methods[0];

        self::assertInstanceOf(AssignStatement::class, $method->statements[1]);
        self::assertSame('maybe', $method->statements[1]->variable);
        self::assertInstanceOf(OptionalExpression::class, $method->statements[1]->expression);
        self::assertInstanceOf(AssignStatement::class, $method->statements[2]);
        self::assertSame('many', $method->statements[2]->variable);
        self::assertInstanceOf(ManyExpression::class, $method->statements[2]->expression);
        self::assertInstanceOf(AssignStatement::class, $method->statements[3]);
        self::assertSame('required', $method->statements[3]->variable);
        self::assertInstanceOf(OneOrMoreExpression::class, $method->statements[3]->expression);
        self::assertInstanceOf(ReturnNullOnNullStatement::class, $method->statements[4]);
    }

    public function testPlannerBuildsActionAlternativeExpressions(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node TypeName(name: token);
token keyword Int ::= 'int';
token keyword Float ::= 'float';
#[start]
rule Type ::= name: Int => TypeName(name) | name: Float => TypeName(name);
GRAMMAR);

        $lowered = GrammarLowerer::lower($document, GrammarIndex::from($document));
        $plan = ParserPlanner::plan($lowered, 'DemoParser', 'DemoTokenType', GrammarIndex::from($document));

        self::assertInstanceOf(ChoiceExpression::class, $plan->methods[0]->returnExpression);
        self::assertInstanceOf(SequenceExpression::class, $plan->methods[0]->returnExpression->choices[0]);
        self::assertInstanceOf(ConstructNodeExpression::class, $plan->methods[0]->returnExpression->choices[0]->returnExpression);
    }

    public function testPlannerBuildsPlainSequenceAlternatives(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token symbol Dot ::= '.';
token symbol Arrow ::= '->';
token Identifier ::= <IdentifierMatcher>;
#[start]
rule MemberSuffix ::= Dot Identifier | Arrow Identifier;
GRAMMAR);

        $lowered = GrammarLowerer::lower($document, GrammarIndex::from($document));
        $plan = ParserPlanner::plan($lowered, 'DemoParser', 'DemoTokenType', GrammarIndex::from($document));

        self::assertInstanceOf(ChoiceExpression::class, $plan->methods[0]->returnExpression);
        self::assertInstanceOf(SequenceExpression::class, $plan->methods[0]->returnExpression->choices[0]);
        self::assertInstanceOf(SequenceExpression::class, $plan->methods[0]->returnExpression->choices[1]);
    }

    public function testPlannerExpandsTokenUnionTypeReferences(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
type BinaryOperator = Plus | Minus;
node Operator(op: BinaryOperator);
token symbol Plus ::= '+';
token symbol Minus ::= '-';
#[start]
rule Operator ::= op: BinaryOperator => Operator(op);
GRAMMAR);

        $index = GrammarIndex::from($document);
        $lowered = GrammarLowerer::lower($document, $index);
        $plan = ParserPlanner::plan($lowered, 'DemoParser', 'DemoTokenType', $index);

        self::assertInstanceOf(AssignStatement::class, $plan->methods[0]->statements[1]);
        self::assertInstanceOf(ChoiceExpression::class, $plan->methods[0]->statements[1]->expression);
    }
}
