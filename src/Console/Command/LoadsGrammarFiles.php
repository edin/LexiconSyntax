<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;
use LexiconSyntax\GrammarLoader;
use LexiconSyntax\ProjectConfig;
use RuntimeException;

trait LoadsGrammarFiles
{
    private function path(Input $input, Output $output): ?string
    {
        $path = $input->argument(0);
        if ($path === null) {
            try {
                return ProjectConfig::loadFor(null)->source;
            } catch (RuntimeException $exception) {
                $output->error(sprintf(
                    'Missing grammar file path and project config could not be loaded: %s',
                    $exception->getMessage()
                ));
            }

            return null;
        }

        return $path;
    }

    private function load(Input $input, Output $output): ?\LexiconSyntax\Ast\GrammarDocumentNode
    {
        $path = $this->path($input, $output);
        if ($path === null) {
            return null;
        }

        return GrammarLoader::loadFile($path);
    }
}
