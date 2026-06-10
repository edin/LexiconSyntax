# Roadmap Notes

This page captures design directions that are not fully implemented yet.

## Portable Action Values

Actions should stay language-neutral. Instead of embedding PHP, actions can use
portable value expressions.

Planned shape:

```lxs
rule Primary : Expression ::=
      name: Identifier => IdentifierExpression(name.value)
    | value: Number => NumberLiteral(value.value);
```

Potential token properties:

```text
token.value
token.type
token.line
token.column
token.span.start
token.span.end
```

Generators can map those to the target language:

```text
PHP      -> $token->value
C#       -> token.Value
JSON AST -> "value"
```

## Fold Metadata

The grammar already supports `#[fold(...)]` metadata. Parser generation should
eventually consume it directly.

Possible syntax:

```lxs
#[fold(node: BinaryExpression, associativity: left)]
rule Additive : Expression ::= Multiplicative (AdditiveOperator Multiplicative)*;

#[fold(node: AssignmentExpression, associativity: right)]
rule Assignment : Expression ::= Postfix (Assign Assignment)?;
```

This would let grammar authors define left and right associative expressions
without relying on structural inference.

## Separate Operator Nodes

Some grammars want separate AST nodes per operator:

```lxs
node AddExpression : Expression(left: Expression, right: Expression);
node SubExpression : Expression(left: Expression, right: Expression);
```

A future fold action could treat suffix alternatives as partial constructors:

```lxs
rule Additive : Expression ::= Multiplicative (
      Plus right: Multiplicative => AddExpression(right)
    | Minus right: Multiplicative => SubExpression(right)
)*;
```

Conceptually:

```text
node = head
for suffix in suffixes:
    node = suffix(node)
```

## JSON AST Target

A JSON AST target would prove that Lexicon Syntax is not tied to PHP AST
classes or token objects.

Example output:

```json
{
  "type": "BinaryExpression",
  "left": { "type": "IdentifierExpression", "name": "left" },
  "op": "+",
  "right": { "type": "IdentifierExpression", "name": "right" }
}
```

This pairs naturally with action property access and scalar node fields:

```lxs
node IdentifierExpression : Expression(name: string);
node BinaryExpression : Expression(left: Expression, op: string, right: Expression);
```

## Runtime Bundling

PHP generation currently depends on `edin/lexicon`. A future bundled-runtime
mode could copy the minimal Lexicon runtime into generated output and rewrite
namespaces.

Possible config:

```json
{
  "source": "grammar.lxs",
  "output": "generated",
  "tokenEnum": "DemoTokenType",
  "parser": "DemoParser",
  "runtime": {
    "mode": "copy",
    "namespace": "Generated\\Runtime"
  }
}
```

Potential modes:

```text
dependency -> generated code uses edin/lexicon
copy       -> generated code includes a minimal copied runtime
```

## Self Hosting

Lexicon Syntax can eventually describe its own grammar in `.lxs`.

Bootstrap path:

```text
PHP attributes parse today's .lxs language
  -> write lexicon-syntax.lxs
  -> generate parser from lexicon-syntax.lxs
  -> compare generated AST with current parser
  -> switch to generated parser when equivalent
```

That would make the PHP attribute parser the bootstrap compiler and `.lxs` the
source of truth.
