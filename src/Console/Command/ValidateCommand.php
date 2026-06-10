<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use LexiconSyntax\Console\CommandInterface;
use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;
use LexiconSyntax\Validation\GrammarDiagnostic;
use LexiconSyntax\Validation\GrammarValidator;

final readonly class ValidateCommand implements CommandInterface
{
    use LoadsGrammarFiles;

    public function name(): string
    {
        return 'validate';
    }

    public function description(): string
    {
        return 'Validate a grammar file or the current project config source.';
    }

    public function execute(Input $input, Output $output): int
    {
        $document = $this->load($input, $output);
        if ($document === null) {
            return 1;
        }

        $result = GrammarValidator::validate($document);
        if (!$result->hasErrors()) {
            $output->line('OK');

            return 0;
        }

        foreach ($result->diagnostics as $diagnostic) {
            $output->line(self::formatDiagnostic($diagnostic));
        }

        return 1;
    }

    private static function formatDiagnostic(GrammarDiagnostic $diagnostic): string
    {
        $location = $diagnostic->token?->location;
        if ($location === null) {
            return $diagnostic->message;
        }

        return sprintf(
            '%s:%d:%d: %s',
            $location->file->path,
            $location->line,
            $location->column,
            $diagnostic->message
        );
    }
}
