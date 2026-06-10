<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use Lexicon\Lexer\Lexer;
use Lexicon\Lexer\SourceFile;
use Lexicon\Parser\Debug\AstPrinter;
use LexiconSyntax\Console\CommandInterface;
use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;
use LexiconSyntax\Generator\AstNodeGenerator;
use LexiconSyntax\Generator\RecursiveParserGenerator;
use LexiconSyntax\Generator\TokenEnumGenerator;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\GrammarLoader;
use LexiconSyntax\Lowering\GrammarLowerer;
use LexiconSyntax\Model\SemanticModelBuilder;
use LexiconSyntax\Planning\Parser\ParserPlanner;
use LexiconSyntax\ProjectConfig;
use LexiconSyntax\Validation\GrammarValidator;
use RuntimeException;

final readonly class ParseCLikeCommand implements CommandInterface
{
    public function name(): string
    {
        return 'parse:c-like';
    }

    public function description(): string
    {
        return 'Parse the C-like sample with generated code.';
    }

    public function execute(Input $input, Output $output): int
    {
        $sourcePath = $input->argument(0) ?? 'examples/c-like.sample.c';

        $config = ProjectConfig::loadFor($input->argument(1) ?? 'examples/c-like');
        $this->generate($config);
        $this->requireGeneratedFiles($config->output);

        $source = file_get_contents($sourcePath);
        if ($source === false) {
            $output->error(sprintf("Source file '%s' could not be read.", $sourcePath));

            return 1;
        }

        $tokenType = 'Generated\\' . $config->tokenEnum;
        $parserType = 'Generated\\' . $config->parser;
        if (!enum_exists($tokenType) || !class_exists($parserType)) {
            $output->error('Generated C-like parser classes could not be loaded.');

            return 1;
        }

        $lexer = Lexer::from($tokenType);
        $tokens = $lexer->scan(new SourceFile($sourcePath, $source));
        if ($lexer->diagnostics->hasErrors()) {
            foreach ($lexer->diagnostics->all() as $diagnostic) {
                $output->error($diagnostic->message);
            }

            return 1;
        }

        $parser = new $parserType($tokens);
        $ast = $parser->parse();

        $output->line(AstPrinter::format($ast, color: true));

        return 0;
    }

    private function generate(ProjectConfig $config): void
    {
        $document = GrammarLoader::loadFile($config->source);
        $validation = GrammarValidator::validate($document);
        if ($validation->hasErrors()) {
            throw new RuntimeException($validation->diagnostics[0]->message);
        }

        $index = GrammarIndex::from($document);
        $model = SemanticModelBuilder::build($document, $index);
        TokenEnumGenerator::generate($model, $config->output, $config->tokenEnum);

        $lowering = GrammarLowerer::lowerWithDiagnostics($document, $index);
        if ($lowering->hasErrors()) {
            throw new RuntimeException($lowering->diagnostics[0]->message);
        }

        AstNodeGenerator::generate($model, $config->output . DIRECTORY_SEPARATOR . 'Ast');
        $plan = ParserPlanner::plan($lowering->grammar, $config->parser, $config->tokenEnum, $index);
        RecursiveParserGenerator::generate($plan, $config->output);
    }

    private function requireGeneratedFiles(string $directory): void
    {
        $rootPaths = glob($directory . DIRECTORY_SEPARATOR . '*.php') ?: [];
        $astPaths = glob($directory . DIRECTORY_SEPARATOR . 'Ast' . DIRECTORY_SEPARATOR . '*.php') ?: [];

        $paths = [
            ...self::interfaceFiles($astPaths),
            ...array_values(array_diff($astPaths, self::interfaceFiles($astPaths))),
            ...$rootPaths,
        ];

        foreach ($paths as $path) {
            require_once $path;
        }
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private static function interfaceFiles(array $paths): array
    {
        return array_values(array_filter(
            $paths,
            fn (string $path): bool => str_contains(file_get_contents($path) ?: '', PHP_EOL . 'interface ')
        ));
    }
}
