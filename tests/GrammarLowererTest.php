<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\GrammarIndex;
use LexiconSyntax\GrammarParser;
use LexiconSyntax\Lowering\Action\ConstructNodeAction;
use LexiconSyntax\Lowering\GrammarLowerer;
use LexiconSyntax\Lowering\Pattern\ActionPattern;
use LexiconSyntax\Lowering\Pattern\CapturePattern;
use LexiconSyntax\Lowering\Pattern\ChoicePattern;
use LexiconSyntax\Lowering\Pattern\ManyPattern;
use LexiconSyntax\Lowering\Pattern\OneOrMorePattern;
use LexiconSyntax\Lowering\Pattern\OptionalPattern;
use LexiconSyntax\Lowering\Pattern\ReferencePattern;
use LexiconSyntax\Lowering\Pattern\SequencePattern;
use LexiconSyntax\Lowering\ReferenceKind;
use PHPUnit\Framework\TestCase;

final class GrammarLowererTest extends TestCase
{
    public function testLowererTurnsSimpleActionRuleIntoCapturedConstructRule(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node Parameter(valueType: rule, name: token);
token keyword Int ::= 'int';
token keyword Float ::= 'float';
token Identifier ::= <IdentifierMatcher>;
rule Type ::= Int | Float;
rule Parameter ::= valueType: Type name: Identifier => Parameter(valueType, name);
GRAMMAR);

        $grammar = GrammarLowerer::lower($document, GrammarIndex::from($document));

        self::assertCount(2, $grammar->rules);
        self::assertInstanceOf(ChoicePattern::class, $grammar->rules[0]->pattern);
        self::assertSame('Type', $grammar->rules[0]->name);

        $rule = $grammar->rules[1];
        self::assertSame('Parameter', $rule->name);
        self::assertInstanceOf(SequencePattern::class, $rule->pattern);
        self::assertInstanceOf(ConstructNodeAction::class, $rule->action);
        self::assertSame('Parameter', $rule->action->nodeName);
        self::assertSame(['valueType', 'name'], $rule->action->arguments);

        self::assertInstanceOf(CapturePattern::class, $rule->pattern->items[0]);
        self::assertSame('valueType', $rule->pattern->items[0]->name);
        self::assertInstanceOf(ReferencePattern::class, $rule->pattern->items[0]->pattern);
        self::assertSame(ReferenceKind::Rule, $rule->pattern->items[0]->pattern->kind);

        self::assertInstanceOf(CapturePattern::class, $rule->pattern->items[1]);
        self::assertSame('name', $rule->pattern->items[1]->name);
        self::assertInstanceOf(ReferencePattern::class, $rule->pattern->items[1]->pattern);
        self::assertSame(ReferenceKind::Token, $rule->pattern->items[1]->pattern->kind);
    }

    public function testLowererNormalizesGroupsAndQuantifiers(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token Identifier ::= <IdentifierMatcher>;
rule Items ::= maybe: Identifier? many: (Identifier)* required: Identifier+;
GRAMMAR);

        $result = GrammarLowerer::lowerWithDiagnostics($document, GrammarIndex::from($document));

        self::assertFalse($result->hasErrors());
        self::assertCount(1, $result->grammar->rules);

        $pattern = $result->grammar->rules[0]->pattern;
        self::assertInstanceOf(SequencePattern::class, $pattern);
        self::assertInstanceOf(CapturePattern::class, $pattern->items[0]);
        self::assertInstanceOf(OptionalPattern::class, $pattern->items[0]->pattern);
        self::assertInstanceOf(CapturePattern::class, $pattern->items[1]);
        self::assertInstanceOf(ManyPattern::class, $pattern->items[1]->pattern);
        self::assertInstanceOf(CapturePattern::class, $pattern->items[2]);
        self::assertInstanceOf(OneOrMorePattern::class, $pattern->items[2]->pattern);
    }

    public function testLowererReportsUnsupportedPattern(): void
    {
        $document = GrammarParser::parse('rule LiteralRule ::= "literal";' . PHP_EOL);

        $result = GrammarLowerer::lowerWithDiagnostics($document, GrammarIndex::from($document));

        self::assertTrue($result->hasErrors());
        self::assertSame('Pattern LexiconSyntax\Ast\StringLiteralExpressionNode cannot be lowered yet.', $result->diagnostics[0]->message);
    }

    public function testLowererSupportsActionAlternatives(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node TypeName(name: token);
token keyword Int ::= 'int';
token keyword Float ::= 'float';
rule Type ::= name: Int => TypeName(name) | name: Float => TypeName(name);
GRAMMAR);

        $result = GrammarLowerer::lowerWithDiagnostics($document, GrammarIndex::from($document));

        self::assertFalse($result->hasErrors());
        self::assertCount(1, $result->grammar->rules);
        self::assertInstanceOf(ChoicePattern::class, $result->grammar->rules[0]->pattern);
        self::assertInstanceOf(ActionPattern::class, $result->grammar->rules[0]->pattern->alternatives[0]);
        self::assertInstanceOf(ConstructNodeAction::class, $result->grammar->rules[0]->pattern->alternatives[0]->action);
        self::assertSame('TypeName', $result->grammar->rules[0]->pattern->alternatives[0]->action->nodeName);
    }
}
