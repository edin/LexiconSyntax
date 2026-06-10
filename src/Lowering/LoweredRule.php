<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering;

use LexiconSyntax\Lowering\Action\LoweredActionInterface;
use LexiconSyntax\Lowering\Pattern\LoweredPatternInterface;

final readonly class LoweredRule
{
    public function __construct(
        public string $name,
        public LoweredPatternInterface $pattern,
        public ?LoweredActionInterface $action,
        public bool $isStart = false
    ) {
    }
}
