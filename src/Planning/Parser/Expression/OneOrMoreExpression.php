<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Expression;

final readonly class OneOrMoreExpression implements ParserExpressionInterface
{
    public function __construct(public ParserExpressionInterface $expression)
    {
    }
}
