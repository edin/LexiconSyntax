<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

use LexiconSyntax\Typing\SemanticTypeInterface;

final readonly class FieldModel
{
    public function __construct(
        public string $name,
        public SemanticTypeInterface $type
    ) {
    }
}
