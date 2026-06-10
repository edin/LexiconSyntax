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
        return 'Create a starter grammar file or example project.';
    }

    public function execute(Input $input, Output $output): int
    {
        if ($input->argument(0) === 'c-like') {
            return $this->createCLikeExample($output);
        }

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

    private function createCLikeExample(Output $output): int
    {
        $files = [
            'c-like.lxs' => self::cLikeGrammar(),
            'c-like.lxs.json' => self::cLikeConfig(),
            'c-like.sample.c' => self::cLikeSample(),
        ];

        foreach (array_keys($files) as $path) {
            if (is_file($path)) {
                $output->error(sprintf("File '%s' already exists.", $path));

                return 1;
            }
        }

        foreach ($files as $path => $contents) {
            file_put_contents($path, $contents);
            $output->line(sprintf('Created %s', $path));
        }

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

    private static function cLikeGrammar(): string
    {
        return self::readExample('c-like.lxs');
    }

    private static function cLikeConfig(): string
    {
        return json_encode([
            'source' => 'c-like.lxs',
            'output' => 'generated/c-like',
            'tokenEnum' => 'CLikeTokenType',
            'parser' => 'CLikeParser',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    private static function cLikeSample(): string
    {
        return self::readExample('c-like.sample.c');
    }

    private static function readExample(string $name): string
    {
        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . $name;
        $contents = file_get_contents($path);

        return $contents === false ? '' : $contents;
    }
}
