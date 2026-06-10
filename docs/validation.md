# Validation

Validation runs over parsed grammar documents before generation.

Current checks include:

- duplicate declarations
- undefined references
- token/rule reference compatibility
- token declarations cannot reference parser rules
- rules cannot use custom token matchers
- directly left-recursive rules
- unreachable declarations
- invalid token categories
- invalid assignmentless token declarations
- duplicate action labels
- undefined action bindings
- unknown action constructor nodes
- action constructor arity
- attribute schema and argument validation
- type declaration validation

Example diagnostics:

```text
Duplicate declaration 'Expression'.
Undefined reference 'Missing'.
Token 'Identifier' cannot reference rule 'Expression'.
Rule 'Expression' is directly left-recursive.
Node 'BinaryExpression' expects 2 arguments, got 1.
```

## Semantic Model

After validation, Lexicon Syntax builds a semantic model with:

- tokens
- rules
- types
- nodes
- attribute schemas
- metadata bags
- resolved rule return types

Rule return types are inferred from actions and rule shapes when possible.
Declared rule return types override inference:

```lxs
rule Primary : Expression ::= value: Number => NumberLiteral(value);
```

This lets generators use a stable type contract even when a rule currently
constructs a specific child node.

## Lowering

Generation uses a lowered grammar model. It normalizes high-level expression
syntax into simpler patterns such as:

- references
- captures
- sequences
- choices
- quantifiers
- action patterns

The parser planner then turns lowered grammar into a language-neutral parser
plan before PHP source is emitted.
