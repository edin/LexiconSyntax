<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Expression;

use LexiconSyntax\Planning\Parser\Statement\ParserStatementInterface;

final readonly class SequenceExpression implements ParserExpressionInterface
{
    /**
     * @param list<ParserStatementInterface> $statements
     */
    public function __construct(
        public array $statements,
        public ParserExpressionInterface $returnExpression
    ) {
    }
}
