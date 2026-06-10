<?php

declare(strict_types=1);

namespace LexiconSyntax\Model;

use LexiconSyntax\GrammarIndex;
use LexiconSyntax\Lowering\Action\ConstructNodeAction;
use LexiconSyntax\Lowering\LoweredGrammar;
use LexiconSyntax\Lowering\LoweredRule;
use LexiconSyntax\Lowering\Pattern\ActionPattern;
use LexiconSyntax\Lowering\Pattern\CapturePattern;
use LexiconSyntax\Lowering\Pattern\ChoicePattern;
use LexiconSyntax\Lowering\Pattern\LoweredPatternInterface;
use LexiconSyntax\Lowering\Pattern\ManyPattern;
use LexiconSyntax\Lowering\Pattern\OneOrMorePattern;
use LexiconSyntax\Lowering\Pattern\OptionalPattern;
use LexiconSyntax\Lowering\Pattern\ReferencePattern;
use LexiconSyntax\Lowering\Pattern\SequencePattern;
use LexiconSyntax\Lowering\ReferenceKind;
use LexiconSyntax\Typing\ArrayType;
use LexiconSyntax\Typing\AttributeTypeResolver;
use LexiconSyntax\Typing\NamedType;
use LexiconSyntax\Typing\NodeNameType;
use LexiconSyntax\Typing\RuleNameType;
use LexiconSyntax\Typing\SemanticTypeInterface;
use LexiconSyntax\Typing\TokenNameType;
use LexiconSyntax\Typing\UnionType;

final class RuleReturnTypeResolver
{
    /** @var array<string, LoweredRule> */
    private array $rules;

    /** @var array<string, SemanticTypeInterface> */
    private array $resolved;

    private function __construct(
        LoweredGrammar $grammar,
        private GrammarIndex $index
    ) {
        $rules = [];
        foreach ($grammar->rules as $rule) {
            $rules[$rule->name] = $rule;
        }

        $this->rules = $rules;
        $this->resolved = [];
    }

    /**
     * @return array<string, SemanticTypeInterface>
     */
    public static function resolve(LoweredGrammar $grammar, GrammarIndex $index): array
    {
        $resolver = new self($grammar, $index);
        $types = [];

        foreach ($grammar->rules as $rule) {
            $types[$rule->name] = $resolver->rule($rule->name);
        }

        return $types;
    }

    /**
     * @param list<string> $seen
     */
    private function rule(string $name, array $seen = []): SemanticTypeInterface
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (in_array($name, $seen, true)) {
            return new RuleNameType($name);
        }

        $rule = $this->rules[$name] ?? null;
        if ($rule === null) {
            return new RuleNameType($name);
        }

        $type = $rule->action instanceof ConstructNodeAction
            ? $this->action($rule->action)
            : $this->pattern($rule->pattern, [...$seen, $name]);

        $this->resolved[$name] = $type;

        return $type;
    }

    /**
     * @param list<string> $seen
     */
    private function pattern(LoweredPatternInterface $pattern, array $seen): SemanticTypeInterface
    {
        if ($pattern instanceof ActionPattern) {
            return $pattern->action instanceof ConstructNodeAction
                ? $this->action($pattern->action)
                : new NamedType('mixed');
        }

        if ($pattern instanceof CapturePattern) {
            return $this->pattern($pattern->pattern, $seen);
        }

        if ($pattern instanceof ChoicePattern) {
            return $this->union(array_map(
                fn (LoweredPatternInterface $alternative): SemanticTypeInterface => $this->pattern($alternative, $seen),
                $pattern->alternatives
            ));
        }

        if ($pattern instanceof ReferencePattern) {
            return match ($pattern->kind) {
                ReferenceKind::Token => new TokenNameType($pattern->name),
                ReferenceKind::Rule => $this->rule($pattern->name, $seen),
                ReferenceKind::Type => $this->type($pattern->name),
            };
        }

        if ($pattern instanceof ManyPattern || $pattern instanceof OneOrMorePattern) {
            return new ArrayType($this->pattern($pattern->pattern, $seen));
        }

        if ($pattern instanceof OptionalPattern) {
            return $this->pattern($pattern->pattern, $seen);
        }

        if ($pattern instanceof SequencePattern) {
            return new NamedType('mixed');
        }

        return new NamedType('mixed');
    }

    private function action(ConstructNodeAction $action): SemanticTypeInterface
    {
        return new NodeNameType($action->nodeName);
    }

    private function type(string $name): SemanticTypeInterface
    {
        $declaration = $this->index->type($name);

        return $declaration === null
            ? new NamedType($name)
            : AttributeTypeResolver::resolveExpression($declaration->value, $this->index);
    }

    /**
     * @param list<SemanticTypeInterface> $types
     */
    private function union(array $types): SemanticTypeInterface
    {
        $types = array_values(array_unique($types, SORT_REGULAR));
        if ($types === []) {
            return new NamedType('mixed');
        }

        if (count($types) === 1) {
            return $types[0];
        }

        $commonNode = $this->commonNode($types);
        if ($commonNode !== null) {
            return new NodeNameType($commonNode);
        }

        /** @var non-empty-list<SemanticTypeInterface> $types */
        return new UnionType($types);
    }

    /**
     * @param list<SemanticTypeInterface> $types
     */
    private function commonNode(array $types): ?string
    {
        foreach ($types as $type) {
            if (!$type instanceof NodeNameType) {
                return null;
            }
        }

        /** @var NodeNameType $first */
        $first = $types[0];
        foreach ($this->ancestors($first->name) as $candidate) {
            foreach ($types as $type) {
                if (!$type instanceof NodeNameType || !in_array($candidate, $this->ancestors($type->name), true)) {
                    continue 2;
                }
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function ancestors(string $name): array
    {
        $ancestors = [];
        while ($name !== '') {
            $ancestors[] = $name;
            $declaration = $this->index->node($name);
            $name = $declaration?->parentName() ?? '';
        }

        return $ancestors;
    }
}
