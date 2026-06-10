<?php

declare(strict_types=1);

namespace LexiconSyntax\Typing;

final readonly class EnumType implements SemanticTypeInterface
{
    /**
     * @param list<string> $cases
     */
    public function __construct(public array $cases)
    {
    }

    public function display(): string
    {
        return 'enum[' . implode(', ', $this->cases) . ']';
    }
}
