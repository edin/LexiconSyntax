<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use LexiconSyntax\Console\CommandInterface;
use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;

final readonly class InitCommand implements CommandInterface
{
    public function name(): string
    {
        return 'init';
    }

    public function description(): string
    {
        return 'Create a starter grammar file.';
    }

    public function execute(Input $input, Output $output): int
    {
        $path = $input->argument(0) ?? 'grammar.lxs';
        if (is_file($path)) {
            $output->error(sprintf("File '%s' already exists.", $path));

            return 1;
        }

        $directory = dirname($path);
        if ($directory !== '.' && !is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            $output->error(sprintf("Directory '%s' could not be created.", $directory));

            return 1;
        }

        file_put_contents($path, self::sampleGrammar());
        $output->line(sprintf('Created %s', $path));

        return 0;
    }

    private static function sampleGrammar(): string
    {
        return <<<'GRAMMAR'
type Associativity = enum[left, right];
type BinaryOperator = Plus | Minus;

attribute rule fold(operators: BinaryOperator[], associativity: Associativity);

node BinaryExpression(left: rule, op: BinaryOperator, right: rule);

token Digit ::= '0' .. '9';
token Number ::= Digit+;
token symbol Plus ::= '+';
token symbol Minus ::= '-';
token eof EndOfFile;
token unknown Unknown;

#[fold(operators: [Plus, Minus], associativity: left)]
rule Expression ::= left: Number (op: BinaryOperator right: Number)*;
GRAMMAR . PHP_EOL;
    }
}
