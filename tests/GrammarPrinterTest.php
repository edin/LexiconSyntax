<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\GrammarParser;
use LexiconSyntax\GrammarPrinter;
use PHPUnit\Framework\TestCase;

final class GrammarPrinterTest extends TestCase
{
    public function testPrinterNormalizesGrammarDocuments(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
import "tokens.lxs";
type BinaryOperator = Plus | Minus;
#[trivia]
token Digit ::= '0'..'9';
token keyword If ::= "if";
token symbol Plus ::= "+";
token eof EndOfFile;
token unknown Unknown;
token Letter ::= 'a' .. 'z' | 'A' .. 'Z' | '_';
token Identifier ::= Letter (Letter | Digit)*;
token Comment ::= <CommentMatcher>;
#[fold(operators: [Plus, Minus], associativity: left, precedence: 10, label: 'math')]
rule Expression : ExpressionNode ::= left: Term rest: ((Plus | Minus) Term)* => Expression(left, rest);
GRAMMAR);

        self::assertSame(str_replace("\n", PHP_EOL, <<<'GRAMMAR'
import "tokens.lxs";
type BinaryOperator = Plus | Minus;
#[trivia]
token Digit ::= '0' .. '9';
token keyword If ::= "if";
token symbol Plus ::= "+";
token eof EndOfFile;
token unknown Unknown;
token Letter ::= 'a' .. 'z' | 'A' .. 'Z' | '_';
token Identifier ::= Letter (Letter | Digit)*;
token Comment ::= <CommentMatcher>;
#[fold(operators: [Plus, Minus], associativity: left, precedence: 10, label: 'math')]
rule Expression : ExpressionNode ::= left: Term rest: ((Plus | Minus) Term)* => Expression(left, rest);
GRAMMAR), GrammarPrinter::format($document));
    }

    public function testPrinterOmitsGrammarComments(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
// comment before declaration
token Digit ::= '0' .. '9'; /* comment after declaration */
GRAMMAR);

        self::assertSame("token Digit ::= '0' .. '9';", GrammarPrinter::format($document));
    }

    public function testPrinterFormatsAttributeDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
attribute rule recover(strategy: enum[panic,synchronize], tokens: identifier[], enabled: bool);
node StatementNode(body: rule);
#[recover(strategy: synchronize, tokens: [Semicolon, CloseBrace], enabled: true)]
rule Statement ::= Block;
GRAMMAR);

        self::assertSame(str_replace("\n", PHP_EOL, <<<'GRAMMAR'
attribute rule recover(strategy: enum[panic, synchronize], tokens: identifier[], enabled: bool);
node StatementNode(body: rule);
#[recover(strategy: synchronize, tokens: [Semicolon, CloseBrace], enabled: true)]
rule Statement ::= Block;
GRAMMAR), GrammarPrinter::format($document));
    }

    public function testPrinterFormatsTypeDeclarations(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
type AttributeValue = string|number|bool|identifier[];
type Associativity = enum[left,right];
GRAMMAR);

        self::assertSame(str_replace("\n", PHP_EOL, <<<'GRAMMAR'
type AttributeValue = string | number | bool | identifier[];
type Associativity = enum[left, right];
GRAMMAR), GrammarPrinter::format($document));
    }
}
