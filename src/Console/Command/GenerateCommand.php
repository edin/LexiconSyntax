<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

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

final readonly class GenerateCommand implements CommandInterface
{
    public function name(): string
    {
        return 'generate';
    }

    public function description(): string
    {
        return 'Generate code using a project or grammar-local config.';
    }

    public function execute(Input $input, Output $output): int
    {
        $config = ProjectConfig::loadFor($input->argument(0));
        $document = GrammarLoader::loadFile($config->source);

        $validation = GrammarValidator::validate($document);
        if ($validation->hasErrors()) {
            foreach ($validation->diagnostics as $diagnostic) {
                $output->error($diagnostic->message);
            }

            return 1;
        }

        $index = GrammarIndex::from($document);
        $model = SemanticModelBuilder::build($document, $index);
        foreach (TokenEnumGenerator::generate($model, $config->output, $config->tokenEnum) as $path) {
            $output->line(sprintf('Generated %s', $path));
        }

        $astOutput = $config->output . DIRECTORY_SEPARATOR . 'Ast';
        $lowering = GrammarLowerer::lowerWithDiagnostics($document, $index);
        if ($lowering->hasErrors()) {
            foreach ($lowering->diagnostics as $diagnostic) {
                $output->error($diagnostic->message);
            }

            return 1;
        }

        foreach (AstNodeGenerator::generate($model, $astOutput) as $path) {
            $output->line(sprintf('Generated %s', $path));
        }

        $plan = ParserPlanner::plan($lowering->grammar, $config->parser, $config->tokenEnum, $index);
        foreach (RecursiveParserGenerator::generate($plan, $config->output) as $path) {
            $output->line(sprintf('Generated %s', $path));
        }

        return 0;
    }
}
