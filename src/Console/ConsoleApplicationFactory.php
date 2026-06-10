<?php

declare(strict_types=1);

namespace LexiconSyntax\Console;

use LexiconSyntax\Console\Command\AstCommand;
use LexiconSyntax\Console\Command\GenerateCommand;
use LexiconSyntax\Console\Command\GenerateAstCommand;
use LexiconSyntax\Console\Command\GenerateParserCommand;
use LexiconSyntax\Console\Command\GenerateTokensCommand;
use LexiconSyntax\Console\Command\InitCommand;
use LexiconSyntax\Console\Command\ParseCommand;
use LexiconSyntax\Console\Command\PrintCommand;
use LexiconSyntax\Console\Command\ValidateCommand;

final readonly class ConsoleApplicationFactory
{
    public static function create(): Application
    {
        return Application::withCommands([
            new ValidateCommand(),
            new PrintCommand(),
            new AstCommand(),
            new InitCommand(),
            new GenerateCommand(),
            new GenerateAstCommand(),
            new GenerateParserCommand(),
            new GenerateTokensCommand(),
            new ParseCommand(),
        ]);
    }
}
