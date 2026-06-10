<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\GrammarParser;
use LexiconSyntax\Validation\GrammarValidator;
use PHPUnit\Framework\TestCase;

final class GrammarValidatorTest extends TestCase
{
    public function testValidatorAcceptsConnectedGrammar(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token Digit ::= '0' .. '9';
token Number ::= Digit+;
token Plus ::= '+';
rule Expression ::= Number (Plus Number)*;
GRAMMAR);

        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
    }

    public function testValidatorAcceptsTokenCustomMatchers(): void
    {
        $document = GrammarParser::parse("token Comment ::= <CommentMatcher>;\n");

        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
    }

    public function testValidatorAcceptsAssignmentlessEofAndUnknownTokens(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token eof EndOfFile;
token unknown Unknown;
GRAMMAR);

        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
    }

    public function testValidatorDoesNotReportUnreachableTriviaTokens(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token trivia Whitespace ::= <WhitespaceMatcher>;
token Number ::= '0';
rule Expression ::= Number;
GRAMMAR);

        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
    }

    public function testValidatorAcceptsRuleActionsUsingCapturedLabels(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node BinaryExpression(op: token, left: rule, right: rule);
token Number ::= '0';
token Plus ::= '+';
rule Expression ::= left: Number op: Plus right: Number => BinaryExpression(op, left, right);
GRAMMAR);

        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
    }

    public function testValidatorReportsDuplicateAndUndefinedDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token Number ::= Digit+;
token Number ::= '0';
rule Expression ::= Missing;
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Duplicate declaration 'Number'.", $messages);
        self::assertContains("Undefined reference 'Digit'.", $messages);
        self::assertContains("Undefined reference 'Missing'.", $messages);
    }

    public function testValidatorReportsDirectLeftRecursionAndUnreachableDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token Number ::= '0';
rule Expression ::= Expression Plus Number | Number;
token Unused ::= 'x';
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Rule 'Expression' is directly left-recursive.", $messages);
        self::assertContains("Declaration 'Unused' is unreachable.", $messages);
    }

    public function testValidatorReportsTokenReferencesToRulesAndRuleCustomMatchers(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token Identifier ::= Expression;
rule Expression ::= <ExpressionMatcher>;
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Token 'Identifier' cannot reference rule 'Expression'.", $messages);
        self::assertContains("Rule 'Expression' cannot use custom matcher 'ExpressionMatcher'.", $messages);
    }

    public function testValidatorReportsInvalidAssignmentlessTokenDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token literal Number;
token eof EndOfFile ::= '';
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Token 'Number' must define an expression unless it is eof or unknown.", $messages);
        self::assertContains("Token 'EndOfFile' cannot define an expression when declared as eof.", $messages);
    }

    public function testValidatorReportsUnknownTokenCategories(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
token banana Identifier ::= 'a';
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Unknown token category 'banana'.", $messages);
    }

    public function testValidatorReportsInvalidRuleActions(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node BinaryExpression(left: rule, right: rule);
token Number ::= value: '0' => NumberLiteral(value);
rule Expression ::= left: Number left: Number => BinaryExpression(left, missing);
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Token 'Number' cannot define an action.", $messages);
        self::assertContains("Duplicate action label 'left'.", $messages);
        self::assertContains("Undefined action binding 'missing'.", $messages);
    }

    public function testValidatorReportsInvalidNodeActions(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
node BinaryExpression(left: rule, right: rule);
node NumberNode(value: token);
node NumberNode(text: string);
token Number ::= '0';
rule Expression ::= value: Number => MissingNode(value) | other: Number => BinaryExpression(other);
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Duplicate node declaration 'NumberNode'.", $messages);
        self::assertContains("Unknown node 'MissingNode'.", $messages);
        self::assertContains("Node 'BinaryExpression' expects 2 arguments, got 1.", $messages);
    }

    public function testValidatorAcceptsDeclaredAttributeModel(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
attribute rule recover(strategy: enum[panic, synchronize], tokens: identifier[], enabled: bool);
token Semicolon ::= ';';
#[recover(strategy: synchronize, tokens: [Semicolon], enabled: true)]
rule Statement ::= Semicolon;
GRAMMAR);

        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
    }

    public function testValidatorReportsAttributeModelErrors(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
attribute rule recover(strategy: enum[panic, synchronize], tokens: identifier[], enabled: bool);
#[recover(strategy: fast, tokens: Semicolon, missing: true)]
token Semicolon ::= ';';
#[missing]
rule Statement ::= Semicolon;
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Attribute 'recover' cannot be used on token.", $messages);
        self::assertContains("Unknown attribute 'missing'.", $messages);
    }

    public function testValidatorReportsAttributeParameterErrors(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
attribute rule recover(strategy: enum[panic, synchronize], tokens: identifier[], enabled: bool);
token Semicolon ::= ';';
#[recover(strategy: fast, tokens: Semicolon, extra: true)]
rule Statement ::= Semicolon;
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Attribute 'recover' parameter 'strategy' expects enum[panic, synchronize].", $messages);
        self::assertContains("Attribute 'recover' parameter 'tokens' expects identifier[].", $messages);
        self::assertContains("Attribute 'recover' has no parameter 'extra'.", $messages);
        self::assertContains("Attribute 'recover' requires parameter 'enabled'.", $messages);
    }

    public function testValidatorChecksTokenAndRuleAttributeTypes(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
attribute rule recover(tokens: token[], fallback: rule);
token Semicolon ::= ';';
#[recover(tokens: [Semicolon], fallback: Expression)]
rule Statement ::= Expression;
rule Expression ::= Semicolon;
GRAMMAR);

        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
    }

    public function testValidatorAcceptsNamedEnumAndTokenUnionTypes(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
type Associativity = enum[left, right];
type BinaryOperator = Plus | Minus;
attribute rule fold(operators: BinaryOperator[], associativity: Associativity);
token Plus ::= '+';
token Minus ::= '-';
token Number ::= '0';
#[fold(operators: [Plus, Minus], associativity: left)]
rule Expression ::= Number (BinaryOperator Number)*;
GRAMMAR);

        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
    }

    public function testValidatorReportsInvalidNamedTypes(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
type BinaryOperator = Plus | Missing;
type Loop = Loop;
attribute rule fold(operators: BinaryOperator[]);
token Plus ::= '+';
#[fold(operators: [Missing])]
rule Expression ::= Plus;
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Unknown type 'Missing'.", $messages);
        self::assertContains("Type 'Loop' recursively references itself.", $messages);
        self::assertContains("Attribute 'fold' parameter 'operators' expects (Plus | Missing)[].", $messages);
    }

    public function testValidatorReportsTokenAndRuleAttributeTypeMismatches(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
attribute rule recover(tokens: token[], fallback: rule);
token Semicolon ::= ';';
#[recover(tokens: [Statement], fallback: Semicolon)]
rule Statement ::= Semicolon;
GRAMMAR);

        $messages = array_map(
            fn ($diagnostic): string => $diagnostic->message,
            GrammarValidator::validate($document)->diagnostics
        );

        self::assertContains("Attribute 'recover' parameter 'tokens' expects token[].", $messages);
        self::assertContains("Attribute 'recover' parameter 'fallback' expects rule.", $messages);
    }
}
