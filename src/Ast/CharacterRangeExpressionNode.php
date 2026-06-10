<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    StringLiteralNode::class,
    GrammarTokenType::Range,
    StringLiteralNode::class,
], factory: 'create')]
final readonly class CharacterRangeExpressionNode implements PrimaryExpressionNodeInterface
{
    public function __construct(
        public StringLiteralNode $start,
        public StringLiteralNode $end
    ) {
    }

    public static function create(
        StringLiteralNode $start,
        Token $range,
        StringLiteralNode $end
    ): self {
        return new self($start, $end);
    }
}
