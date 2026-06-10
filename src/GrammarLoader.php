<?php

declare(strict_types=1);

namespace LexiconSyntax;

use Lexicon\Lexer\SourceFile;
use LexiconSyntax\Ast\GrammarDocumentNode;
use RuntimeException;

final readonly class GrammarLoader
{
    public static function loadFile(string $path): GrammarDocumentNode
    {
        $loaded = [];

        return self::loadResolvedFile(self::normalizePath($path), [], $loaded);
    }

    /**
     * @param list<string> $stack
     * @param array<string, true> $loaded
     */
    private static function loadResolvedFile(string $path, array $stack, array &$loaded): GrammarDocumentNode
    {
        if (isset($loaded[$path])) {
            return new GrammarDocumentNode([], [], [], []);
        }

        if (in_array($path, $stack, true)) {
            throw new RuntimeException(sprintf(
                'Import cycle detected: %s.',
                implode(' -> ', [...$stack, $path])
            ));
        }

        if (!is_file($path)) {
            throw new RuntimeException(sprintf("Grammar file '%s' does not exist.", $path));
        }

        $source = file_get_contents($path);
        if ($source === false) {
            throw new RuntimeException(sprintf("Grammar file '%s' could not be read.", $path));
        }

        $document = GrammarParser::parseSource(new SourceFile($path, $source));
        $stack[] = $path;

        $imports = [];
        $attributeDeclarations = [];
        $typeDeclarations = [];
        $nodeDeclarations = [];
        $declarations = [];

        foreach ($document->imports as $import) {
            $imported = self::loadResolvedFile(
                self::resolveImport($path, $import->pathValue()),
                $stack,
                $loaded
            );

            $imports = [...$imports, ...$imported->imports];
            $attributeDeclarations = [...$attributeDeclarations, ...$imported->attributeDeclarations];
            $typeDeclarations = [...$typeDeclarations, ...$imported->typeDeclarations];
            $nodeDeclarations = [...$nodeDeclarations, ...$imported->nodeDeclarations];
            $declarations = [...$declarations, ...$imported->declarations];
        }

        $loaded[$path] = true;

        return new GrammarDocumentNode(
            $imports,
            [...$attributeDeclarations, ...$document->attributeDeclarations],
            [...$nodeDeclarations, ...$document->nodeDeclarations],
            [...$declarations, ...$document->declarations],
            [...$typeDeclarations, ...$document->typeDeclarations]
        );
    }

    private static function resolveImport(string $fromPath, string $importPath): string
    {
        if (self::isAbsolutePath($importPath)) {
            return self::normalizePath($importPath);
        }

        return self::normalizePath(dirname($fromPath) . DIRECTORY_SEPARATOR . $importPath);
    }

    private static function normalizePath(string $path): string
    {
        $realPath = realpath($path);
        if ($realPath !== false) {
            return $realPath;
        }

        return $path;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
