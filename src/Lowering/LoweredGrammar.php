<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering;

final readonly class LoweredGrammar
{
    /**
     * @param list<LoweredRule> $rules
     */
    public function __construct(public array $rules)
    {
    }
}
