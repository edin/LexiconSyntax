<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering\Action;

final readonly class ConstructNodeAction implements LoweredActionInterface
{
    /**
     * @param list<string> $arguments
     */
    public function __construct(
        public string $nodeName,
        public array $arguments
    ) {
    }
}
