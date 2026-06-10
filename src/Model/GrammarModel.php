<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

final readonly class GrammarModel
{
    /**
     * @param array<string, TokenModel> $tokens
     * @param array<string, RuleModel> $rules
     * @param array<string, TypeModel> $types
     * @param array<string, NodeModel> $nodes
     * @param array<string, AttributeSchemaModel> $attributes
     */
    public function __construct(
        public array $tokens,
        public array $rules,
        public array $types,
        public array $nodes,
        public array $attributes
    ) {
    }
}
