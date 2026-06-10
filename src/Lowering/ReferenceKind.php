<?php

declare(strict_types=1);

namespace LexiconSyntax\Lowering;

enum ReferenceKind
{
    case Token;
    case Rule;
    case Type;
}
