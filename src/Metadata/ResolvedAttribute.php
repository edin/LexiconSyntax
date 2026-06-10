<?php

declare(strict_types=1);

namespace LexiconSyntax\Metadata;

final readonly class ResolvedAttribute
{
    /**
     * @param array<string, mixed> $arguments
     * @param list<mixed> $positionalArguments
     */
    public function __construct(
        private string $name,
        private array $arguments,
        private array $positionalArguments
    ) {
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->arguments[$name] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return list<mixed>
     */
    public function positionalArguments(): array
    {
        return $this->positionalArguments;
    }
}
