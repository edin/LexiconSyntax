<?php

declare(strict_types=1);

namespace LexiconSyntax;

use Lexicon\Lexer\Lexer;
use Lexicon\Lexer\SourceFile;
use Lexicon\Parser\Parser;
use LexiconSyntax\Ast\GrammarDocumentNode;

final readonly class GrammarParser
{
    public static function parse(string $source): GrammarDocumentNode
    {
        return self::parseSource($source);
    }

    public static function parseSource(string|SourceFile $source): GrammarDocumentNode
    {
        $tokens = Lexer::from(GrammarTokenType::class)->scan($source);
        $parser = Parser::fromTokens($tokens);

        return $parser->parse(GrammarDocumentNode::class);
    }

    public static function parseFile(string $path): GrammarDocumentNode
    {
        $source = file_get_contents($path);
        if ($source === false) {
            throw new \RuntimeException(sprintf("Grammar file '%s' could not be read.", $path));
        }

        return self::parseSource(new SourceFile($path, $source));
    }
}
