<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Expression;

final readonly class FoldLeftExpression implements ParserExpressionInterface
{
    public function __construct(
        public ParserExpressionInterface $head,
        public ParserExpressionInterface $tail,
        public string $nodeName
    ) {
    }
}
