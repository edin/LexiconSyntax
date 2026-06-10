<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Expression;

final readonly class ChoiceExpression implements ParserExpressionInterface
{
    /**
     * @param non-empty-list<ParserExpressionInterface> $choices
     */
    public function __construct(public array $choices)
    {
    }
}
