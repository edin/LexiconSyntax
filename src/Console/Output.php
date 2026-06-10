<?php

declare(strict_types=1);

namespace LexiconSyntax\Console;

class Output
{
    public function write(string $message): void
    {
        fwrite(STDOUT, $message);
    }

    public function line(string $message = ''): void
    {
        $this->write($message . PHP_EOL);
    }

    public function error(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}
