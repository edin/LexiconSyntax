<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\ProjectConfig;
use PHPUnit\Framework\TestCase;

final class ProjectConfigTest extends TestCase
{
    public function testConfigPathDefaultsToProjectConfig(): void
    {
        self::assertSame('lexicon-syntax.json', ProjectConfig::configPathFor(null));
        self::assertSame('lexicon-syntax.json', ProjectConfig::configPathFor(''));
    }

    public function testConfigPathAcceptsExplicitJson(): void
    {
        self::assertSame('examples/c-like.lxs.json', ProjectConfig::configPathFor('examples/c-like.lxs.json'));
    }

    public function testConfigPathDerivesFromGrammarPath(): void
    {
        self::assertSame('examples/c-like.lxs.json', ProjectConfig::configPathFor('examples/c-like.lxs'));
        self::assertSame('examples/c-like.lxs.json', ProjectConfig::configPathFor('examples/c-like'));
    }
}
