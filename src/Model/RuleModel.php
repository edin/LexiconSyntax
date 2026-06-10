<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

use LexiconSyntax\Ast\ExpressionNodeInterface;
use LexiconSyntax\Metadata\MetadataBag;
use LexiconSyntax\Typing\SemanticTypeInterface;

final readonly class RuleModel
{
    /**
     * @param list<string> $references
     */
    public function __construct(
        public string $name,
        public ExpressionNodeInterface $expression,
        public array $references,
        public MetadataBag $metadata,
        public SemanticTypeInterface $returnType
    ) {
    }
}
