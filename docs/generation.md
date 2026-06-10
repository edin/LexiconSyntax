# Generation

Lexicon Syntax can generate PHP code from `.lxs` grammars.

The current PHP generation pipeline is:

```text
.lxs grammar
  -> parsed grammar document
  -> validation
  -> semantic model
  -> lowered grammar
  -> parser plan
  -> PHP token enum, AST nodes, and recursive parser
```

## Token Enum Generation

Token declarations become a Lexicon token enum.

```lxs
token keyword If ::= "if";
token symbol Plus ::= "+";
token Identifier ::= <IdentifierMatcher>;
token eof EndOfFile;
```

Generated shape:

```php
enum CLikeTokenType
{
    #[Keyword('if')]
    case If;

    #[Symbol('+')]
    case Plus;

    #[Identifier(IdentifierTokenMatcher::class)]
    case Identifier;

    #[EndOfFile]
    case EndOfFile;
}
```

Known matcher names use Lexicon built-ins. Unknown custom matchers generate
matcher stubs.

## AST Node Generation

Node declarations become readonly PHP classes. Parent nodes become interfaces.

```lxs
node Expression;
node BinaryExpression : Expression(left: Expression, op: token, right: Expression);
node NumberLiteral : Expression(value: token);
```

Generated shape:

```php
interface ExpressionNode
{
}

final readonly class BinaryExpressionNode implements ExpressionNode
{
    public function __construct(
        public ExpressionNode $left,
        public Token $op,
        public ExpressionNode $right
    ) {
    }
}
```

Rule return type annotations help generated node fields narrow from `mixed` to
the declared node/interface type.

```lxs
rule Additive : Expression ::= Multiplicative (AdditiveOperator Multiplicative)*;
```

## Parser Generation

Rules become recursive descent parser methods. The generated parser consumes
Lexicon tokens through `TokenStream`.

Supported generated parser behavior includes:

- token matches
- rule calls
- choices
- optional items
- many and one-or-more repetitions
- constructor actions
- pass-through helper rules
- comma-separated list lowering
- left-associative binary expression folding for `A (Op A)*` shapes

For example:

```lxs
rule ParameterList ::= Parameter (Comma Parameter)*;
```

returns a list of `ParameterNode` values.

```lxs
rule Additive ::= Multiplicative (AdditiveOperator Multiplicative)*;
```

can fold into `BinaryExpressionNode` when the grammar declares a compatible
`BinaryExpression` node.

## AST Printing

Generated parsers can be inspected with Lexicon's `AstPrinter`.

`AstPrinter::format($ast, color: true)` colors:

- nodes: bright cyan
- identifiers: green
- literals: magenta
- keywords: blue
- symbols/operators: yellow
- structural labels and lists: dim

The C-like demo command uses colored AST output:

```bash
php bin/lsyn parse examples/c-like
```

## Current Limitations

The generator is intentionally small and conservative.

Not everything in the grammar language has a generated-parser lowering yet.
Unsupported shapes should become diagnostics rather than hidden behavior.

Important missing or early areas:

- property access in actions such as `name.value`
- named action arguments
- configurable fold node names and associativity from `#[fold]`
- right-associative fold generation
- JSON AST output mode
- copied/bundled Lexicon runtime mode
