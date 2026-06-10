<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use LexiconSyntax\Console\CommandInterface;
use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;
use LexiconSyntax\GrammarPrinter;

final readonly class PrintCommand implements CommandInterface
{
    use LoadsGrammarFiles;

    public function name(): string
    {
        return 'print';
    }

    public function description(): string
    {
        return 'Pretty-print a grammar file.';
    }

    public function execute(Input $input, Output $output): int
    {
        $document = $this->load($input, $output);
        if ($document === null) {
            return 1;
        }

        $output->line(GrammarPrinter::format($document));

        return 0;
    }
}
