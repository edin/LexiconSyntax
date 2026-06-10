<?php

declare(strict_types=1);

namespace LexiconSyntax\Typing;

final readonly class NamedType implements SemanticTypeInterface
{
    public function __construct(public string $name)
    {
    }

    public function display(): string
    {
        return $this->name;
    }
}
