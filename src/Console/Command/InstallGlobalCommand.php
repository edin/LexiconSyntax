<?php

declare(strict_types=1);

namespace LexiconSyntax\Console\Command;

use LexiconSyntax\Console\CommandInterface;
use LexiconSyntax\Console\Input;
use LexiconSyntax\Console\Output;
use RuntimeException;

final readonly class InstallGlobalCommand implements CommandInterface
{
    public function name(): string
    {
        return 'install-global';
    }

    public function description(): string
    {
        return 'Install a global lsyn launcher.';
    }

    public function execute(Input $input, Output $output): int
    {
        $targetDirectory = $input->argument(0) ?? self::defaultBinDirectory();
        if ($targetDirectory === null) {
            $output->error('Could not determine global bin directory. Pass one explicitly.');

            return 1;
        }

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0777, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException(sprintf("Global bin directory '%s' could not be created.", $targetDirectory));
        }

        $source = self::launcherSourcePath();
        $installed = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $path = $targetDirectory . DIRECTORY_SEPARATOR . 'lsyn.bat';
            file_put_contents($path, self::windowsLauncher($source));
            $installed[] = $path;
        } else {
            $path = $targetDirectory . DIRECTORY_SEPARATOR . 'lsyn';
            file_put_contents($path, self::unixLauncher($source));
            chmod($path, 0755);
            $installed[] = $path;
        }

        foreach ($installed as $path) {
            $output->line(sprintf('Installed %s', $path));
        }

        if (!self::isOnPath($targetDirectory)) {
            $output->line(sprintf("Add '%s' to PATH to run lsyn from anywhere.", $targetDirectory));
        }

        return 0;
    }

    private static function launcherSourcePath(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'lsyn';
    }

    private static function windowsLauncher(string $source): string
    {
        return "@echo off\r\nphp \"" . $source . "\" %*\r\n";
    }

    private static function unixLauncher(string $source): string
    {
        return "#!/usr/bin/env sh\nexec php " . escapeshellarg($source) . ' "$@"' . "\n";
    }

    private static function defaultBinDirectory(): ?string
    {
        $composerHome = getenv('COMPOSER_HOME');
        if (is_string($composerHome) && $composerHome !== '') {
            return $composerHome . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin';
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $appData = getenv('APPDATA');
            if (is_string($appData) && $appData !== '') {
                return $appData . DIRECTORY_SEPARATOR . 'Composer' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin';
            }

            return null;
        }

        $home = getenv('HOME');
        if (!is_string($home) || $home === '') {
            return null;
        }

        return $home . DIRECTORY_SEPARATOR . '.composer' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin';
    }

    private static function isOnPath(string $directory): bool
    {
        $path = getenv('PATH');
        if (!is_string($path)) {
            return false;
        }

        $target = rtrim(strtolower(self::normalize($directory)), DIRECTORY_SEPARATOR);
        foreach (explode(PATH_SEPARATOR, $path) as $entry) {
            if (rtrim(strtolower(self::normalize($entry)), DIRECTORY_SEPARATOR) === $target) {
                return true;
            }
        }

        return false;
    }

    private static function normalize(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }
}
