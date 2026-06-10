<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering\Pattern;

final readonly class ChoicePattern implements LoweredPatternInterface
{
    /**
     * @param non-empty-list<LoweredPatternInterface> $alternatives
     */
    public function __construct(public array $alternatives)
    {
    }
}
