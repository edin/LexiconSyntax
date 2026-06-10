<?php

declare(strict_types=1);

namespace LexiconSyntax;

use RuntimeException;

final readonly class ProjectConfig
{
    public function __construct(
        public string $source,
        public string $output,
        public string $tokenEnum = 'GeneratedTokenType',
        public string $parser = 'GeneratedParser'
    ) {
    }

    public static function load(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf("Config file '%s' does not exist.", $path));
        }

        $source = file_get_contents($path);
        if ($source === false) {
            throw new RuntimeException(sprintf("Config file '%s' could not be read.", $path));
        }

        try {
            $data = json_decode($source, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException(sprintf("Config file '%s' is not valid JSON: %s", $path, $exception->getMessage()));
        }

        if (!is_array($data)) {
            throw new RuntimeException(sprintf("Config file '%s' must contain a JSON object.", $path));
        }

        $sourcePath = self::stringValue($data, 'source', $path);
        $outputPath = self::stringValue($data, 'output', $path);
        $tokenEnum = isset($data['tokenEnum'])
            ? self::stringValue($data, 'tokenEnum', $path)
            : 'GeneratedTokenType';
        $parser = isset($data['parser'])
            ? self::stringValue($data, 'parser', $path)
            : 'GeneratedParser';

        $baseDirectory = dirname($path);

        return new self(
            self::resolvePath($baseDirectory, $sourcePath),
            self::resolvePath($baseDirectory, $outputPath),
            $tokenEnum,
            $parser
        );
    }

    public static function loadFor(?string $path): self
    {
        return self::load(self::configPathFor($path));
    }

    public static function configPathFor(?string $path): string
    {
        if ($path === null || $path === '') {
            return 'lexicon-syntax.json';
        }

        if (str_ends_with($path, '.json')) {
            return $path;
        }

        if (str_ends_with($path, '.lxs')) {
            return $path . '.json';
        }

        return $path . '.lxs.json';
    }

    /**
     * @param array<mixed> $data
     */
    private static function stringValue(array $data, string $key, string $path): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new RuntimeException(sprintf("Config file '%s' requires string property '%s'.", $path, $key));
        }

        return $value;
    }

    private static function resolvePath(string $baseDirectory, string $path): string
    {
        if (self::isAbsolutePath($path)) {
            return $path;
        }

        return $baseDirectory . DIRECTORY_SEPARATOR . $path;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
