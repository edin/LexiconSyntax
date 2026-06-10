<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\GrammarParser;
use LexiconSyntax\Metadata\MetadataResolver;
use PHPUnit\Framework\TestCase;

final class MetadataResolverTest extends TestCase
{
    public function testMetadataCanReadAttributesWithMagicGetters(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
#[fold(operators: [Plus, Minus], associativity: left, precedence: 10, enabled: true, label: "math")]
rule Expression ::= Term;
GRAMMAR);

        $metadata = MetadataResolver::forDeclaration($document->declarations[0]);

        self::assertTrue(isset($metadata->fold));
        self::assertSame(['Plus', 'Minus'], $metadata->fold->operators);
        self::assertSame('left', $metadata->fold->associativity);
        self::assertSame(10, $metadata->fold->precedence);
        self::assertTrue($metadata->fold->enabled);
        self::assertSame('math', $metadata->fold->label);
        self::assertNull($metadata->fold->missing);
    }

    public function testMetadataCanReadPositionalArguments(): void
    {
        $document = GrammarParser::parse(<<<'GRAMMAR'
#[matcher(CommentMatcher)]
token Comment ::= <CommentMatcher>;
GRAMMAR);

        $metadata = MetadataResolver::forDeclaration($document->declarations[0]);
        $matcher = $metadata->get('matcher');

        self::assertNotNull($matcher);
        self::assertSame('CommentMatcher', $matcher->get('0'));
        self::assertSame(['CommentMatcher'], $matcher->positionalArguments());
    }
}
