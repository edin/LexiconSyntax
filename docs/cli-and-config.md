# CLI And Config

The executable is `lsyn`.

During development:

```bash
php bin/lsyn help
```

After install:

```bash
lsyn help
```

## Commands

Current commands include:

```bash
php bin/lsyn validate examples/c-like.lxs
php bin/lsyn print examples/c-like.lxs
php bin/lsyn ast examples/c-like.lxs
php bin/lsyn generate examples/c-like
php bin/lsyn generate:tokens examples/c-like.lxs generated CLikeTokenType
php bin/lsyn generate:ast examples/c-like.lxs generated/Ast
php bin/lsyn generate:parser examples/c-like.lxs generated CLikeParser CLikeTokenType
php bin/lsyn parse:c-like examples/c-like.sample.c examples/c-like
php bin/lsyn init
php bin/lsyn init c-like
php bin/lsyn install-global
```

`init c-like` creates a ready-to-generate demo project in the current
directory:

```text
c-like.lxs
c-like.lxs.json
c-like.sample.c
```

## Project Config

Generation uses JSON config.

```json
{
  "source": "c-like.lxs",
  "output": "generated/c-like",
  "tokenEnum": "CLikeTokenType",
  "parser": "CLikeParser"
}
```

Paths inside config are relative to the config file.

## Config Resolution

`generate` accepts a project config, a grammar path, or a grammar basename.

Resolution rules:

```text
no arg          -> lexicon-syntax.json
*.json          -> exact config path
*.lxs           -> *.lxs.json
anything else   -> <arg>.lxs.json
```

Examples:

```bash
php bin/lsyn generate
php bin/lsyn generate lexicon-syntax.json
php bin/lsyn generate examples/c-like.lxs
php bin/lsyn generate examples/c-like
```

For a grammar named:

```text
examples/c-like.lxs
```

the grammar-local config is:

```text
examples/c-like.lxs.json
```

## Global Install

Composer can expose `lsyn` globally after publishing:

```bash
composer global require edin/lexicon-syntax
lsyn help
```

During development from source, run the binary directly:

```bash
composer install
php bin/lsyn help
```

On Windows, Composer's global bin directory is commonly:

```text
%APPDATA%\Composer\vendor\bin
```

The project also includes a direct launcher command:

```bash
php bin/lsyn install-global
```
