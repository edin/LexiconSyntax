# Language

Lexicon Syntax grammar files use the `.lxs` extension. A file can declare
imports, types, attributes, nodes, tokens, and rules.

## Imports

Imports split grammars across files. Imported files are parsed and merged before
validation.

```lxs
import "tokens.lxs";
```

## Tokens

Tokens map to Lexicon token enum cases.

```lxs
token keyword If ::= "if";
token symbol Plus ::= "+";
token Identifier ::= <IdentifierMatcher>;
token trivia Whitespace ::= <WhitespaceMatcher>;
token eof EndOfFile;
token unknown Unknown;
```

Supported token categories:

- `keyword`
- `symbol`
- `trivia`
- `eof`
- `unknown`

Token expressions can use references, string literals, character ranges,
choices, sequences, groups, quantifiers, and custom matchers.

```lxs
token Digit ::= '0' .. '9';
token Letter ::= 'a' .. 'z' | 'A' .. 'Z' | '_';
token Identifier ::= Letter (Letter | Digit)*;
token Number ::= Digit+;
token String ::= <StringMatcher>;
```

Common matcher names such as `IdentifierMatcher`, `NumberMatcher`,
`StringMatcher`, `WhitespaceMatcher`, `LineCommentMatcher`, and
`BlockCommentMatcher` are mapped to Lexicon built-ins by the PHP token
generator.

## Types

Type declarations create reusable semantic aliases. They are used by node
fields, attribute schemas, rule return annotations, and generator planning.

```lxs
type Associativity = enum[left, right];
type BinaryOperator = Plus | Minus | Star | Slash;
type AttributeValue = string | number | bool | identifier[];
```

Types can reference tokens, rules, nodes, built-in scalar names, enums, unions,
and arrays.

## Nodes

Node declarations describe the AST model.

```lxs
node Expression;
node Statement;

node BinaryExpression : Expression(left: Expression, op: token, right: Expression);
node NumberLiteral : Expression(value: token);
node Block : Statement(statements: Statement[]);
```

The `: Parent` form declares a node hierarchy. In PHP generation, parent nodes
become interfaces and child nodes implement them.

## Attributes

Attributes attach metadata to tokens, rules, and other declarations. Attribute
schemas describe valid parameters.

```lxs
attribute rule fold(operators: BinaryOperator[], associativity: Associativity);

#[fold(operators: [Plus, Minus], associativity: left)]
rule Additive : Expression ::= Multiplicative (AdditiveOperator Multiplicative)*;
```

Attribute values currently support identifiers, strings, numbers, booleans, and
arrays.

Built-in schemas include common token attributes and rule metadata such as
`#[start]`, `#[between(...)]`, and `#[fold(...)]`.

## Rules

Rules describe parser productions.

```lxs
#[start]
rule Program ::= items: Declaration* EndOfFile => Program(items);

rule Declaration ::= StructDeclaration | FunctionDeclaration | VariableDeclaration;
```

Rules can include an optional return type annotation:

```lxs
rule Additive : Expression ::= Multiplicative (AdditiveOperator Multiplicative)*;
rule ArgumentList : Expression[] ::= Expression (Comma Expression)*;
```

Rule expressions support:

- references: `Expression`, `Identifier`, `Plus`
- labels: `name: Identifier`
- sequence: `Type Identifier Semicolon`
- choice: `A | B | C`
- grouping: `(A | B)`
- quantifiers: `?`, `*`, `+`
- actions: `=> NodeName(arg1, arg2)`

## Actions

Actions are portable constructor expressions. They do not embed PHP code.

```lxs
rule Parameter ::= valueType: Type name: Identifier
    => Parameter(valueType, name);
```

Actions can be placed on alternatives:

```lxs
rule Type ::=
      name: Int => TypeName(name)
    | Struct name: Identifier => StructTypeName(name);
```

The generated parser currently supports constructor actions and identifier
pass-through actions. Property access such as `token.value` is a planned
extension.

## Comments

Grammar files support line and block comments.

```lxs
// line comment

/*
 * block comment
 */
```
