<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering\Pattern;

final readonly class OptionalPattern implements LoweredPatternInterface
{
    public function __construct(public LoweredPatternInterface $pattern)
    {
    }
}
