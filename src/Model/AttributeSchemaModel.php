<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

final readonly class AttributeSchemaModel
{
    /**
     * @param list<FieldModel> $parameters
     */
    public function __construct(
        public string $target,
        public string $name,
        public array $parameters
    ) {
    }

    public function key(): string
    {
        return $this->target . ':' . $this->name;
    }
}
