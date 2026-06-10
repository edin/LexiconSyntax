<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser;

final readonly class ParserPlan
{
    /**
     * @param list<ParserMethodPlan> $methods
     */
    public function __construct(
        public string $name,
        public string $tokenEnumName,
        public string $startMethodName,
        public string $startRuleName,
        public array $methods
    ) {
    }
}
