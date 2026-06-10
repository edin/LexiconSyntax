<?php

declare(strict_types=1);

namespace LexiconSyntax\Typing;

final readonly class UnionType implements SemanticTypeInterface
{
    /**
     * @param non-empty-list<SemanticTypeInterface> $types
     */
    public function __construct(public array $types)
    {
    }

    public function display(): string
    {
        return implode(' | ', array_map(
            fn (SemanticTypeInterface $type): string => $type->display(),
            $this->types
        ));
    }
}
