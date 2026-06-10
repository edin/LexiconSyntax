<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering\Pattern;

use LexiconSyntax\Lowering\ReferenceKind;

final readonly class ReferencePattern implements LoweredPatternInterface
{
    public function __construct(
        public string $name,
        public ReferenceKind $kind
    ) {
    }
}
