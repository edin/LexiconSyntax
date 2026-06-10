<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

use LexiconSyntax\Ast\ExpressionNodeInterface;
use LexiconSyntax\Metadata\MetadataBag;

final readonly class TokenModel
{
    /**
     * @param list<string> $references
     */
    public function __construct(
        public string $name,
        public ?string $category,
        public ?ExpressionNodeInterface $expression,
        public array $references,
        public MetadataBag $metadata
    ) {
    }
}
