<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use LexiconSyntax\Console\CommandInterface;
use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;
use LexiconSyntax\Generator\RecursiveParserGenerator;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Lowering\GrammarLowerer;
use LexiconSyntax\Planning\Parser\ParserPlanner;
use LexiconSyntax\Validation\GrammarValidator;

final readonly class GenerateParserCommand implements CommandInterface
{
    use LoadsGrammarFiles;

    public function name(): string
    {
        return 'generate:parser';
    }

    public function description(): string
    {
        return 'Generate a simple recursive parser.';
    }

    public function execute(Input $input, Output $output): int
    {
        $document = $this->load($input, $output);
        if ($document === null) {
            return 1;
        }

        $outputDirectory = $input->argument(1);
        if ($outputDirectory === null) {
            $output->error('Missing output directory.');

            return 1;
        }

        $validation = GrammarValidator::validate($document);
        if ($validation->hasErrors()) {
            foreach ($validation->diagnostics as $diagnostic) {
                $output->error($diagnostic->message);
            }

            return 1;
        }

        $index = GrammarIndex::from($document);
        $lowering = GrammarLowerer::lowerWithDiagnostics($document, $index);
        if ($lowering->hasErrors()) {
            foreach ($lowering->diagnostics as $diagnostic) {
                $output->error($diagnostic->message);
            }

            return 1;
        }

        $parserName = $input->argument(2) ?? 'GeneratedParser';
        $tokenEnumName = $input->argument(3) ?? 'GeneratedTokenType';
        $plan = ParserPlanner::plan($lowering->grammar, $parserName, $tokenEnumName, $index);

        foreach (RecursiveParserGenerator::generate($plan, $outputDirectory) as $path) {
            $output->line(sprintf('Generated %s', $path));
        }

        return 0;
    }
}
