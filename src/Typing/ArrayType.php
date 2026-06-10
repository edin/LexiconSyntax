<?php

declare(strict_types=1);

namespace LexiconSyntax\Typing;

final readonly class ArrayType implements SemanticTypeInterface
{
    public function __construct(public SemanticTypeInterface $itemType)
    {
    }

    public function display(): string
    {
        $item = $this->itemType instanceof UnionType
            ? '(' . $this->itemType->display() . ')'
            : $this->itemType->display();

        return $item . '[]';
    }
}
