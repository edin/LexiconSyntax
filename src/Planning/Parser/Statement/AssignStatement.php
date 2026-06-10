<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Statement;

use LexiconSyntax\Planning\Parser\Expression\ParserExpressionInterface;

final readonly class AssignStatement implements ParserStatementInterface
{
    public function __construct(
        public string $variable,
        public ParserExpressionInterface $expression
    ) {
    }
}
