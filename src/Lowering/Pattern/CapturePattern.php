<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering\Pattern;

final readonly class CapturePattern implements LoweredPatternInterface
{
    public function __construct(
        public string $name,
        public LoweredPatternInterface $pattern
    ) {
    }
}
