<?php

declare(strict_types=1);

namespace LexiconSyntax\Console;

use Throwable;

final readonly class Application
{
    /**
     * @param array<string, CommandInterface> $commands
     */
    private function __construct(private array $commands)
    {
    }

    /**
     * @param list<CommandInterface> $commands
     */
    public static function withCommands(array $commands): self
    {
        $indexed = [];
        foreach ($commands as $command) {
            $indexed[$command->name()] = $command;
        }

        return new self($indexed);
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv, ?Output $output = null): int
    {
        $output ??= new Output();
        $commandName = $argv[1] ?? 'help';

        if ($commandName === 'help' || $commandName === '--help' || $commandName === '-h') {
            $this->printHelp($output);

            return 0;
        }

        $command = $this->commands[$commandName] ?? null;
        if ($command === null) {
            $output->error(sprintf("Unknown command '%s'.", $commandName));
            $this->printHelp($output);

            return 1;
        }

        try {
            return $command->execute(new Input(array_slice($argv, 2)), $output);
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }

    private function printHelp(Output $output): void
    {
        $output->line('Lexicon Syntax');
        $output->line();
        $output->line('Usage:');
        $output->line('  lsyn <command> [arguments]');
        $output->line();
        $output->line('Commands:');

        foreach ($this->commands as $command) {
            $output->line(sprintf('  %-10s %s', $command->name(), $command->description()));
        }
    }
}
