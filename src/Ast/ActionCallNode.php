<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    IdentifierNode::class,
    GrammarTokenType::OpenParen,
    [Part::SeparatedBy, ActionValueNodeInterface::class, GrammarTokenType::Comma, true],
    GrammarTokenType::CloseParen,
], factory: 'create')]
final readonly class ActionCallNode implements ActionValueNodeInterface
{
    /**
     * @param list<ActionValueNodeInterface> $arguments
     */
    public function __construct(
        public IdentifierNode $name,
        public array $arguments
    ) {
    }

    /**
     * @param list<ActionValueNodeInterface> $arguments
     */
    public static function create(
        IdentifierNode $name,
        Token $open,
        array $arguments,
        Token $close
    ): self {
        return new self($name, $arguments);
    }
}
