<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Expression;

final readonly class ConstructNodeExpression implements ParserExpressionInterface
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
