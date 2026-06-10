<?php

declare(strict_types=1);

namespace LexiconSyntax\Console;

final readonly class Input
{
    /**
     * @param list<string> $arguments
     */
    public function __construct(private array $arguments)
    {
    }

    /**
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    public function argument(int $index): ?string
    {
        return $this->arguments[$index] ?? null;
    }
}
