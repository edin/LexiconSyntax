<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

use LexiconSyntax\Ast\TypeExpressionNode;
use LexiconSyntax\Typing\SemanticTypeInterface;

final readonly class TypeModel
{
    public function __construct(
        public string $name,
        public TypeExpressionNode $expression,
        public SemanticTypeInterface $resolvedType
    ) {
    }
}
