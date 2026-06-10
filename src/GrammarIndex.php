<?php

declare(strict_types=1);

namespace LexiconSyntax;

use Lexicon\Lexer\Location;
use Lexicon\Lexer\SourceFile;
use Lexicon\Lexer\Token;
use Lexicon\Lexer\TokenGroup;
use LexiconSyntax\Ast\AttributeDeclarationNode;
use LexiconSyntax\Ast\AttributeParameterNode;
use LexiconSyntax\Ast\AttributeTypeNode;
use LexiconSyntax\Ast\DeclarationNodeInterface;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\IdentifierNode;
use LexiconSyntax\Ast\NodeDeclarationNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\Ast\TypeDeclarationNode;
use LexiconSyntax\Validation\GrammarDiagnostic;

final readonly class GrammarIndex
{
    /**
     * @param array<string, DeclarationNodeInterface> $declarations
     * @param array<string, NodeDeclarationNode> $nodes
     * @param array<string, AttributeDeclarationNode> $attributeSchemas
     * @param array<string, TypeDeclarationNode> $types
     * @param list<GrammarDiagnostic> $diagnostics
     */
    private function __construct(
        private array $declarations,
        private array $nodes,
        private array $attributeSchemas,
        private array $types,
        public array $diagnostics
    ) {
    }

    public static function from(GrammarDocumentNode $document): self
    {
        $diagnostics = [];
        $declarations = self::buildDeclarations($document, $diagnostics);
        $nodes = self::buildNodes($document, $diagnostics);
        $attributeSchemas = self::buildAttributeSchemas($document, $diagnostics);
        $types = self::buildTypes($document, $diagnostics);

        return new self($declarations, $nodes, $attributeSchemas, $types, $diagnostics);
    }

    /**
     * @return array<string, DeclarationNodeInterface>
     */
    public function declarations(): array
    {
        return $this->declarations;
    }

    /**
     * @return array<string, NodeDeclarationNode>
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return array<string, AttributeDeclarationNode>
     */
    public function attributeSchemas(): array
    {
        return $this->attributeSchemas;
    }

    /**
     * @return array<string, TypeDeclarationNode>
     */
    public function types(): array
    {
        return $this->types;
    }

    public function declaration(string $name): ?DeclarationNodeInterface
    {
        return $this->declarations[$name] ?? null;
    }

    public function token(string $name): ?TokenDeclarationNode
    {
        $declaration = $this->declaration($name);

        return $declaration instanceof TokenDeclarationNode ? $declaration : null;
    }

    public function rule(string $name): ?RuleDeclarationNode
    {
        $declaration = $this->declaration($name);

        return $declaration instanceof RuleDeclarationNode ? $declaration : null;
    }

    public function node(string $name): ?NodeDeclarationNode
    {
        return $this->nodes[$name] ?? null;
    }

    public function attributeSchema(string $target, string $name): ?AttributeDeclarationNode
    {
        return $this->attributeSchemas[self::attributeSchemaKey($target, $name)] ?? null;
    }

    public function type(string $name): ?TypeDeclarationNode
    {
        return $this->types[$name] ?? null;
    }

    public function hasAttributeWithName(string $name): bool
    {
        foreach ($this->attributeSchemas as $schema) {
            if ($schema->name->token->value === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<GrammarDiagnostic> $diagnostics
     * @return array<string, DeclarationNodeInterface>
     */
    private static function buildDeclarations(GrammarDocumentNode $document, array &$diagnostics): array
    {
        $declarations = [];

        foreach ($document->declarations as $declaration) {
            $name = $declaration->nameToken()->value;
            if (isset($declarations[$name])) {
                $diagnostics[] = new GrammarDiagnostic(sprintf("Duplicate declaration '%s'.", $name), $declaration->nameToken());
                continue;
            }

            $declarations[$name] = $declaration;
        }

        return $declarations;
    }

    /**
     * @param list<GrammarDiagnostic> $diagnostics
     * @return array<string, NodeDeclarationNode>
     */
    private static function buildNodes(GrammarDocumentNode $document, array &$diagnostics): array
    {
        $nodes = [];

        foreach ($document->nodeDeclarations as $declaration) {
            $name = $declaration->name->token->value;
            if (isset($nodes[$name])) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Duplicate node declaration '%s'.", $name),
                    $declaration->name->token
                );
            }

            $nodes[$name] = $declaration;
        }

        return $nodes;
    }

    /**
     * @param list<GrammarDiagnostic> $diagnostics
     * @return array<string, AttributeDeclarationNode>
     */
    private static function buildAttributeSchemas(GrammarDocumentNode $document, array &$diagnostics): array
    {
        $schemas = self::builtInAttributeSchemas();
        $documentSchemas = [];

        foreach ($document->attributeDeclarations as $declaration) {
            $key = self::attributeSchemaKey($declaration->targetName(), $declaration->name->token->value);
            if (isset($documentSchemas[$key])) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Duplicate attribute declaration '%s' for %s.", $declaration->name->token->value, $declaration->targetName()),
                    $declaration->name->token
                );
            }

            $documentSchemas[$key] = $declaration;
            $schemas[$key] = $declaration;
        }

        return $schemas;
    }

    /**
     * @param list<GrammarDiagnostic> $diagnostics
     * @return array<string, TypeDeclarationNode>
     */
    private static function buildTypes(GrammarDocumentNode $document, array &$diagnostics): array
    {
        $types = [];

        foreach ($document->typeDeclarations as $declaration) {
            $name = $declaration->name->token->value;
            if (isset($types[$name])) {
                $diagnostics[] = new GrammarDiagnostic(
                    sprintf("Duplicate type declaration '%s'.", $name),
                    $declaration->name->token
                );
            }

            $types[$name] = $declaration;
        }

        return $types;
    }

    /**
     * @return array<string, AttributeDeclarationNode>
     */
    private static function builtInAttributeSchemas(): array
    {
        $schemas = [];

        foreach ([
            self::schema('token', 'trivia'),
            self::schema('token', 'keyword'),
            self::schema('token', 'symbol'),
            self::schema('token', 'literal'),
            self::schema('token', 'matcher', [['class', 'identifier', false, []]]),
            self::schema('rule', 'start'),
            self::schema('rule', 'between', [
                ['open', 'identifier', false, []],
                ['close', 'identifier', false, []],
            ]),
            self::schema('rule', 'fold', [
                ['operators', 'identifier', true, []],
                ['associativity', 'enum', false, ['left', 'right']],
                ['precedence', 'number', false, []],
                ['label', 'string', false, []],
            ]),
        ] as $schema) {
            $schemas[self::attributeSchemaKey($schema->targetName(), $schema->name->token->value)] = $schema;
        }

        return $schemas;
    }

    /**
     * @param list<array{0: string, 1: string, 2: bool, 3: list<string>}> $parameters
     */
    private static function schema(string $target, string $name, array $parameters = []): AttributeDeclarationNode
    {
        return new AttributeDeclarationNode(
            self::syntheticToken($target),
            self::syntheticToken($target),
            new IdentifierNode(self::syntheticToken($name)),
            array_map(
                fn (array $parameter): AttributeParameterNode => new AttributeParameterNode(
                    new IdentifierNode(self::syntheticToken($parameter[0])),
                    self::syntheticToken(':'),
                    new AttributeTypeNode(
                        new IdentifierNode(self::syntheticToken($parameter[1])),
                        array_map(
                            fn (string $value): IdentifierNode => new IdentifierNode(self::syntheticToken($value)),
                            $parameter[3]
                        ),
                        $parameter[2] ? self::syntheticToken('[') : null,
                        $parameter[2] ? self::syntheticToken(']') : null
                    )
                ),
                $parameters
            ),
            self::syntheticToken(';')
        );
    }

    private static function syntheticToken(string $value): Token
    {
        return new Token(
            GrammarTokenType::Identifier,
            $value,
            new Location(new SourceFile('<schema>', ''), 0, 1, 1),
            TokenGroup::Literal
        );
    }

    private static function attributeSchemaKey(string $target, string $name): string
    {
        return $target . ':' . $name;
    }
}
