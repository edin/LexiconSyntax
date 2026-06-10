<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering\Pattern;

use LexiconSyntax\Lowering\Action\LoweredActionInterface;

final readonly class ActionPattern implements LoweredPatternInterface
{
    public function __construct(
        public LoweredPatternInterface $pattern,
        public LoweredActionInterface $action
    ) {
    }
}
