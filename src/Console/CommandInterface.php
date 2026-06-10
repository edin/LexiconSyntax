<?php

declare(strict_types=1);

namespace LexiconSyntax\Console;

interface CommandInterface
{
    public function name(): string;

    public function description(): string;

    public function execute(Input $input, Output $output): int;
}
