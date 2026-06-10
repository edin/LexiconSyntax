<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Expression;

final readonly class ArrayExpression implements ParserExpressionInterface
{
    /**
     * @param list<ParserExpressionInterface> $items
     */
    public function __construct(public array $items)
    {
    }
}
