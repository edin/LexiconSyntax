<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use Lexicon\Parser\Debug\AstPrinter;
use LexiconSyntax\Console\CommandInterface;
use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;

final readonly class AstCommand implements CommandInterface
{
    use LoadsGrammarFiles;

    public function name(): string
    {
        return 'ast';
    }

    public function description(): string
    {
        return 'Print the parsed AST shape.';
    }

    public function execute(Input $input, Output $output): int
    {
        $document = $this->load($input, $output);
        if ($document === null) {
            return 1;
        }

        $output->line(AstPrinter::format($document));

        return 0;
    }
}
