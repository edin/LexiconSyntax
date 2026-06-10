<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser;

use LexiconSyntax\Planning\Parser\Expression\ParserExpressionInterface;
use LexiconSyntax\Planning\Parser\Statement\ParserStatementInterface;

final readonly class ParserMethodPlan
{
    /**
     * @param list<ParserStatementInterface> $statements
     */
    public function __construct(
        public string $name,
        public string $ruleName,
        public array $statements,
        public ParserExpressionInterface $returnExpression
    ) {
    }
}
