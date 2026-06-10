<?php

declare(strict_types=1);

namespace LexiconSyntax\Console;

final class BufferedOutput extends Output
{
    public string $stdout = '';
    public string $stderr = '';

    public function write(string $message): void
    {
        $this->stdout .= $message;
    }

    public function error(string $message): void
    {
        $this->stderr .= $message . PHP_EOL;
    }
}
