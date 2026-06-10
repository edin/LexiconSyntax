<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

use LexiconSyntax\Ast\ExpressionWalker;
use LexiconSyntax\Ast\GrammarDocumentNode;
use LexiconSyntax\Ast\RuleDeclarationNode;
use LexiconSyntax\Ast\TokenDeclarationNode;
use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Lowering\GrammarLowerer;
use LexiconSyntax\Metadata\MetadataResolver;
use LexiconSyntax\Typing\NamedType;
use LexiconSyntax\Typing\AttributeTypeResolver;

final readonly class SemanticModelBuilder
{
    public static function build(GrammarDocumentNode $document, ?GrammarIndex $index = null): GrammarModel
    {
        $index ??= GrammarIndex::from($document);

        return new GrammarModel(
            self::tokens($document),
            self::rules($document, $index),
            self::types($index),
            self::nodes($index),
            self::attributes($index)
        );
    }

    /**
     * @return array<string, TokenModel>
     */
    private static function tokens(GrammarDocumentNode $document): array
    {
        $tokens = [];

        foreach ($document->declarations as $declaration) {
            if (!$declaration instanceof TokenDeclarationNode) {
                continue;
            }

            $expression = $declaration->expression();
            $tokens[$declaration->nameToken()->value] = new TokenModel(
                $declaration->nameToken()->value,
                $declaration->categoryName(),
                $expression,
                $expression === null ? [] : self::referenceNames($expression),
                MetadataResolver::forDeclaration($declaration)
            );
        }

        return $tokens;
    }

    /**
     * @return array<string, RuleModel>
     */
    private static function rules(GrammarDocumentNode $document, GrammarIndex $index): array
    {
        $rules = [];
        $lowering = GrammarLowerer::lowerWithDiagnostics($document, $index);
        $returnTypes = $lowering->hasErrors()
            ? []
            : RuleReturnTypeResolver::resolve($lowering->grammar, $index);

        foreach ($document->declarations as $declaration) {
            if (!$declaration instanceof RuleDeclarationNode) {
                continue;
            }

            $returnType = $declaration->returnType === null
                ? ($returnTypes[$declaration->nameToken()->value] ?? new NamedType('mixed'))
                : AttributeTypeResolver::resolveExpression($declaration->returnType, $index);

            $rules[$declaration->nameToken()->value] = new RuleModel(
                $declaration->nameToken()->value,
                $declaration->expression(),
                self::referenceNames($declaration->expression()),
                MetadataResolver::forDeclaration($declaration),
                $returnType
            );
        }

        return $rules;
    }

    /**
     * @return array<string, TypeModel>
     */
    private static function types(GrammarIndex $index): array
    {
        $types = [];

        foreach ($index->types() as $name => $declaration) {
            $types[$name] = new TypeModel(
                $name,
                $declaration->value,
                AttributeTypeResolver::resolveExpression($declaration->value, $index)
            );
        }

        return $types;
    }

    /**
     * @return array<string, NodeModel>
     */
    private static function nodes(GrammarIndex $index): array
    {
        $nodes = [];

        foreach ($index->nodes() as $name => $declaration) {
            $nodes[$name] = new NodeModel($name, self::fields($declaration->fields, $index), $declaration->parentName());
        }

        return $nodes;
    }

    /**
     * @return array<string, AttributeSchemaModel>
     */
    private static function attributes(GrammarIndex $index): array
    {
        $attributes = [];

        foreach ($index->attributeSchemas() as $declaration) {
            $attribute = new AttributeSchemaModel(
                $declaration->targetName(),
                $declaration->name->token->value,
                self::fields($declaration->parameters, $index)
            );

            $attributes[$attribute->key()] = $attribute;
        }

        return $attributes;
    }

    /**
     * @param list<\LexiconSyntax\Ast\AttributeParameterNode> $parameters
     * @return list<FieldModel>
     */
    private static function fields(array $parameters, GrammarIndex $index): array
    {
        return array_map(
            fn ($parameter): FieldModel => new FieldModel(
                $parameter->name->token->value,
                AttributeTypeResolver::resolve($parameter->type, $index)
            ),
            $parameters
        );
    }

    /**
     * @return list<string>
     */
    private static function referenceNames(\LexiconSyntax\Ast\ExpressionNodeInterface $expression): array
    {
        return array_values(array_unique(array_map(
            fn ($token): string => $token->value,
            ExpressionWalker::references($expression)
        )));
    }
}
