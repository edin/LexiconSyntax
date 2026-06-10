<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering\Pattern;

final readonly class SequencePattern implements LoweredPatternInterface
{
    /**
     * @param list<LoweredPatternInterface> $items
     */
    public function __construct(public array $items)
    {
    }
}
