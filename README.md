# Lexicon Syntax

Lexicon Syntax is a companion grammar parser and validator for the
`edin/lexicon` package. It parses compact `token` and `rule` declarations
into an AST, validates common grammar mistakes, and can pretty-print the
grammar back into a normalized BNF-like format.

See [docs/README.md](docs/README.md) for the language, CLI, generation,
validation, and roadmap notes.

```ebnf
token Digit ::= '0' .. '9';
token Letter ::= 'a' .. 'z' | 'A' .. 'Z' | '_';
token Identifier ::= Letter (Letter | Digit)*;
token Number ::= Digit+;

rule Expression ::= Term ((Plus | Minus) Term)*;
rule Term ::= Factor ((Star | Slash) Factor)*;
rule Factor ::= Number | GroupedExpression;
rule GroupedExpression ::= OpenParen Expression CloseParen;
```

```php
use LexiconSyntax\GrammarParser;
use LexiconSyntax\GrammarPrinter;
use LexiconSyntax\Validation\GrammarValidator;

$document = GrammarParser::parse($source);

echo GrammarPrinter::format($document);

$result = GrammarValidator::validate($document);
foreach ($result->diagnostics as $diagnostic) {
    echo $diagnostic->message, PHP_EOL;
}
```

## CLI

The package exposes the `lsyn` executable through Composer:

To create a C-like demo project in an empty directory:

```bash
lsyn init c-like
lsyn validate
lsyn generate
lsyn parse
```

Those commands read `lexicon-syntax.json` from the current project folder.

After the package is published, Composer can install it globally:

```bash
composer global require edin/lexicon-syntax
lsyn help
```

During development from source, run the binary directly:

```bash
composer install
php bin/lsyn help
```

Make sure Composer's global `vendor/bin` directory is on `PATH`. On
Windows this is commonly:

```text
%APPDATA%\Composer\vendor\bin
```
