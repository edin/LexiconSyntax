<?php

declare(strict_types=1);

namespace LexiconSyntax\Tests;

use LexiconSyntax\ProjectConfig;
use PHPUnit\Framework\TestCase;

final class ProjectConfigTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lxs_config_' . bin2hex(random_bytes(4));
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($this->directory);
    }

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

    public function testConfigLoadsSamplePathRelativeToConfigFile(): void
    {
        $configPath = $this->directory . DIRECTORY_SEPARATOR . 'demo.lxs.json';
        file_put_contents($configPath, json_encode([
            'source' => 'demo.lxs',
            'output' => 'generated',
            'sample' => 'demo.txt',
        ], JSON_PRETTY_PRINT));

        $config = ProjectConfig::load($configPath);

        self::assertSame($this->directory . DIRECTORY_SEPARATOR . 'demo.txt', $config->sample);
    }
}
