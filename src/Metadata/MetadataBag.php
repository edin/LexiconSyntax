<?php

declare(strict_types=1);

namespace LexiconSyntax\Metadata;

final readonly class MetadataBag
{
    /**
     * @param array<string, ResolvedAttribute> $attributes
     */
    public function __construct(private array $attributes)
    {
    }

    public function __get(string $name): ?ResolvedAttribute
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function has(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function get(string $name): ?ResolvedAttribute
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @return array<string, ResolvedAttribute>
     */
    public function all(): array
    {
        return $this->attributes;
    }
}
