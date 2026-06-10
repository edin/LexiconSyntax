<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Expression;

final readonly class RuleCallExpression implements ParserExpressionInterface
{
    public function __construct(
        public string $ruleName,
        public string $methodName
    ) {
    }
}
