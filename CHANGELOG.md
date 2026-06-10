# Changelog

## 0.1.1

- Fixed the `lsyn` Composer bin entry so global installs use Composer's
  generated autoload path.
- Added `lsyn init c-like` to create a ready-to-generate C-like demo project in
  the current directory.

## 0.1.0

- Initial Lexicon Syntax grammar parser and validator.
- Added `.lxs` declarations for tokens, rules, types, attributes, and nodes.
- Added parser recipe AST using Lexicon attributes.
- Added grammar pretty-printer and colored AST debugging output.
- Added semantic model with resolved types and metadata.
- Added PHP generation for token enums, AST nodes, and recursive parsers.
- Added C-like demo grammar, generated parser smoke test, and VS Code syntax files.
