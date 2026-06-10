<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

final readonly class NodeModel
{
    /**
     * @param list<FieldModel> $fields
     */
    public function __construct(
        public string $name,
        public array $fields,
        public ?string $parentName = null
    ) {
    }
}
