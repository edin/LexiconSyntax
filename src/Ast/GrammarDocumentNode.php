<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Parser\Attributes\Many;

#[Many([
    ImportDeclarationNode::class,
    TypeDeclarationNode::class,
    AttributeDeclarationNode::class,
    NodeDeclarationNode::class,
    TokenDeclarationNode::class,
    RuleDeclarationNode::class,
])]
final readonly class GrammarDocumentNode
{
    /** @var list<ImportDeclarationNode> */
    public array $imports;

    /** @var list<AttributeDeclarationNode> */
    public array $attributeDeclarations;

    /** @var list<TypeDeclarationNode> */
    public array $typeDeclarations;

    /** @var list<NodeDeclarationNode> */
    public array $nodeDeclarations;

    /** @var list<DeclarationNodeInterface> */
    public array $declarations;

    /**
     * @param list<object> $imports
     * @param list<AttributeDeclarationNode>|null $attributeDeclarations
     * @param list<NodeDeclarationNode>|null $nodeDeclarations
     * @param list<DeclarationNodeInterface>|null $declarations
     * @param list<TypeDeclarationNode>|null $typeDeclarations
     */
    public function __construct(
        array $imports,
        ?array $attributeDeclarations = null,
        ?array $nodeDeclarations = null,
        ?array $declarations = null,
        ?array $typeDeclarations = null
    )
    {
        if ($attributeDeclarations !== null || $nodeDeclarations !== null || $declarations !== null || $typeDeclarations !== null) {
            /** @var list<ImportDeclarationNode> $imports */
            $this->imports = $imports;
            $this->attributeDeclarations = $attributeDeclarations ?? [];
            $this->nodeDeclarations = $nodeDeclarations ?? [];
            $this->declarations = $declarations ?? [];
            $this->typeDeclarations = $typeDeclarations ?? [];

            return;
        }

        $items = $imports;
        $parsedImports = [];
        $parsedAttributeDeclarations = [];
        $parsedTypeDeclarations = [];
        $parsedNodeDeclarations = [];
        $parsedDeclarations = [];

        foreach ($items as $item) {
            if ($item instanceof ImportDeclarationNode) {
                $parsedImports[] = $item;
                continue;
            }

            if ($item instanceof AttributeDeclarationNode) {
                $parsedAttributeDeclarations[] = $item;
                continue;
            }

            if ($item instanceof TypeDeclarationNode) {
                $parsedTypeDeclarations[] = $item;
                continue;
            }

            if ($item instanceof NodeDeclarationNode) {
                $parsedNodeDeclarations[] = $item;
                continue;
            }

            if ($item instanceof DeclarationNodeInterface) {
                $parsedDeclarations[] = $item;
            }
        }

        $this->imports = $parsedImports;
        $this->attributeDeclarations = $parsedAttributeDeclarations;
        $this->typeDeclarations = $parsedTypeDeclarations;
        $this->nodeDeclarations = $parsedNodeDeclarations;
        $this->declarations = $parsedDeclarations;
    }
}
