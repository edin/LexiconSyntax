<?php

declare(strict_types=1);

namespace LexiconSyntax\Ast;

use Lexicon\Lexer\Token;
use Lexicon\Parser\Attributes\Sequence;
use Lexicon\Parser\Part;
use LexiconSyntax\GrammarTokenType;

#[Sequence([
    SequenceExpressionNode::class,
    [Part::OptionalSequence, GrammarTokenType::ActionArrow, ActionValueNodeInterface::class],
], factory: 'create')]
final readonly class AlternativeExpressionNode
{
    /**
     * @param array{0: Token, 1: ActionValueNodeInterface}|null $action
     */
    public static function create(
        ExpressionNodeInterface $pattern,
        ?array $action
    ): ExpressionNodeInterface {
        if ($action === null) {
            return $pattern;
        }

        return new ActionExpressionNode($pattern, $action[0], $action[1]);
    }
}
