<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use LexiconSyntax\Console\CommandInterface;
use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;
use LexiconSyntax\Generator\TokenEnumGenerator;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Model\SemanticModelBuilder;
use LexiconSyntax\Validation\GrammarValidator;

final readonly class GenerateTokensCommand implements CommandInterface
{
    use LoadsGrammarFiles;

    public function name(): string
    {
        return 'generate:tokens';
    }

    public function description(): string
    {
        return 'Generate a Lexicon token enum.';
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

        $enumName = $input->argument(2) ?? 'GeneratedTokenType';
        $index = GrammarIndex::from($document);
        $model = SemanticModelBuilder::build($document, $index);

        foreach (TokenEnumGenerator::generate($model, $outputDirectory, $enumName) as $path) {
            $output->line(sprintf('Generated %s', $path));
        }

        return 0;
    }
}
