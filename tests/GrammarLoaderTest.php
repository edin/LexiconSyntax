<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\GrammarLoader;
use LexiconSyntax\GrammarPrinter;
use LexiconSyntax\Validation\GrammarValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GrammarLoaderTest extends TestCase
{
    public function testLoaderMergesImportedGrammarFiles(): void
    {
        $directory = self::makeTempDirectory();
        $tokensPath = $directory . DIRECTORY_SEPARATOR . 'tokens.lxs';
        $mainPath = $directory . DIRECTORY_SEPARATOR . 'main.lxs';
        self::writeFile($tokensPath, <<<'GRAMMAR'
token Digit ::= '0' .. '9';
token Number ::= Digit+;
GRAMMAR);
        self::writeFile($mainPath, <<<'GRAMMAR'
import "tokens.lxs";
rule Expression ::= Number;
GRAMMAR);

        $document = GrammarLoader::loadFile($mainPath);

        self::assertCount(3, $document->declarations);
        self::assertSame(realpath($tokensPath), $document->declarations[0]->nameToken()->location->file->path);
        self::assertSame(realpath($mainPath), $document->declarations[2]->nameToken()->location->file->path);
        self::assertFalse(GrammarValidator::validate($document)->hasErrors());
        self::assertSame(str_replace("\n", PHP_EOL, <<<'GRAMMAR'
token Digit ::= '0' .. '9';
token Number ::= Digit+;
rule Expression ::= Number;
GRAMMAR), GrammarPrinter::format($document));
    }

    public function testLoaderLoadsSharedImportsOnlyOnce(): void
    {
        $directory = self::makeTempDirectory();
        self::writeFile($directory . DIRECTORY_SEPARATOR . 'tokens.lxs', "token Number ::= '0';\n");
        self::writeFile($directory . DIRECTORY_SEPARATOR . 'left.lxs', "import \"tokens.lxs\";\nrule Left ::= Number;\n");
        self::writeFile($directory . DIRECTORY_SEPARATOR . 'right.lxs', "import \"tokens.lxs\";\nrule Right ::= Number;\n");
        self::writeFile($directory . DIRECTORY_SEPARATOR . 'main.lxs', "import \"left.lxs\";\nimport \"right.lxs\";\nrule Main ::= Left Right;\n");

        $document = GrammarLoader::loadFile($directory . DIRECTORY_SEPARATOR . 'main.lxs');

        self::assertCount(4, $document->declarations);
    }

    public function testLoaderPreservesDiagnosticLocationsFromImportedFiles(): void
    {
        $directory = self::makeTempDirectory();
        $tokensPath = $directory . DIRECTORY_SEPARATOR . 'tokens.lxs';
        self::writeFile($tokensPath, "token Number ::= Missing;\n");
        self::writeFile($directory . DIRECTORY_SEPARATOR . 'main.lxs', "import \"tokens.lxs\";\nrule Main ::= Number;\n");

        $document = GrammarLoader::loadFile($directory . DIRECTORY_SEPARATOR . 'main.lxs');
        $diagnostics = GrammarValidator::validate($document)->diagnostics;

        self::assertSame("Undefined reference 'Missing'.", $diagnostics[0]->message);
        self::assertSame(realpath($tokensPath), $diagnostics[0]->token?->location->file->path);
    }

    public function testLoaderReportsImportCycles(): void
    {
        $directory = self::makeTempDirectory();
        self::writeFile($directory . DIRECTORY_SEPARATOR . 'a.lxs', "import \"b.lxs\";\ntoken A ::= 'a';\n");
        self::writeFile($directory . DIRECTORY_SEPARATOR . 'b.lxs', "import \"a.lxs\";\ntoken B ::= 'b';\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import cycle detected');

        GrammarLoader::loadFile($directory . DIRECTORY_SEPARATOR . 'a.lxs');
    }

    private static function makeTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lexicon-syntax-' . bin2hex(random_bytes(6));
        mkdir($directory);

        return $directory;
    }

    private static function writeFile(string $path, string $contents): void
    {
        file_put_contents($path, $contents);
    }
}
