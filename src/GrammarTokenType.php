<?php

declare(strict_types=1);

namespace LexiconSyntax;

use Lexicon\Lexer\Attributes\EndOfFile;
use Lexicon\Lexer\Attributes\Identifier;
use Lexicon\Lexer\Attributes\Keyword;
use Lexicon\Lexer\Attributes\RegexPattern;
use Lexicon\Lexer\Attributes\Symbol;
use Lexicon\Lexer\Attributes\Trivia;
use Lexicon\Lexer\Attributes\Unknown;
use Lexicon\Lexer\Matchers\BlockCommentTokenMatcher;
use Lexicon\Lexer\Matchers\LineCommentTokenMatcher;
use Lexicon\Lexer\Matchers\WhitespaceTokenMatcher;

enum GrammarTokenType
{
    #[Keyword('token')]
    case TokenKeyword;

    #[Keyword('rule')]
    case RuleKeyword;

    #[Keyword('attribute')]
    case AttributeKeyword;

    #[Keyword('node')]
    case NodeKeyword;

    #[Keyword('type')]
    case TypeKeyword;

    #[Keyword('import')]
    case ImportKeyword;

    #[Keyword('grammar')]
    case GrammarKeyword;

    #[Keyword('true')]
    case TrueKeyword;

    #[Keyword('false')]
    case FalseKeyword;

    #[Identifier]
    case Identifier;

    #[RegexPattern('/\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"/')]
    case StringLiteral;

    #[RegexPattern('/[0-9]+(?:\.[0-9]+)?/')]
    case NumberLiteral;

    #[Symbol('#[')]
    case AttributeStart;

    #[Symbol('::=')]
    case Define;

    #[Symbol('=>')]
    case ActionArrow;

    #[Symbol('=')]
    case Equal;

    #[Symbol('..')]
    case Range;

    #[Symbol('|')]
    case Pipe;

    #[Symbol('?')]
    case Question;

    #[Symbol('*')]
    case Star;

    #[Symbol('+')]
    case Plus;

    #[Symbol('(')]
    case OpenParen;

    #[Symbol(')')]
    case CloseParen;

    #[Symbol('[')]
    case OpenBracket;

    #[Symbol(']')]
    case CloseBracket;

    #[Symbol('<')]
    case OpenAngle;

    #[Symbol('>')]
    case CloseAngle;

    #[Symbol(',')]
    case Comma;

    #[Symbol(':')]
    case Colon;

    #[Symbol(';')]
    case Semicolon;

    #[Trivia(LineCommentTokenMatcher::class)]
    case LineComment;

    #[Trivia(BlockCommentTokenMatcher::class)]
    case BlockComment;

    #[Trivia(WhitespaceTokenMatcher::class)]
    case Whitespace;

    #[Unknown]
    case Unknown;

    #[EndOfFile]
    case EndOfFile;
}
