<?php

declare(strict_types=1);

namespace LexiconSyntax\Planning\Parser\Statement;

final readonly class ReturnNullOnNullStatement implements ParserStatementInterface
{
    public function __construct(public string $variable)
    {
    }
}
