<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

final class Php83CompatibilityTest extends TestCase
{
    private const ROOT = __DIR__ . '/..';

    public function testRuntimeIsSupportedPhp83OrNewerAndRequiredExtensionsAreLoaded(): void
    {
        self::assertSame(8, PHP_MAJOR_VERSION);
        self::assertGreaterThanOrEqual(3, PHP_MINOR_VERSION);
        self::assertLessThan(6, PHP_MINOR_VERSION);
        self::assertTrue(extension_loaded('mysqli'), 'mysqli extension is required.');
        self::assertTrue(extension_loaded('pdo_mysql'), 'pdo_mysql extension is required.');
        self::assertTrue(extension_loaded('mbstring'), 'mbstring extension is required.');
    }

    public function testAllApplicationPhpFilesHaveValidSyntax(): void
    {
        $files = $this->phpFiles();
        self::assertNotEmpty($files);

        foreach ($files as $file) {
            $command = sprintf(
                '%s -l %s 2>&1',
                escapeshellarg(PHP_BINARY),
                escapeshellarg($file)
            );
            exec($command, $output, $exitCode);
            self::assertSame(0, $exitCode, implode("\n", $output) . "\nFile: {$file}");
        }
    }

    public function testRemovedAndDeprecatedRuntimeApisAreAbsentFromApplicationCode(): void
    {
        $files = $this->phpFiles();
        self::assertNotEmpty($files);
        $patterns = [
            '/\bmysql_(?:connect|query|fetch|num_rows|error|close|select_db|insert_id)\s*\(/i',
            '/\bereg(?:i)?\s*\(/i',
            '/\beach\s*\(/i',
            '/\bcreate_function\s*\(/i',
            '/\bunserialize\s*\([^,)]*\)/i',
            '/\bget_magic_quotes_gpc\s*\(/i',
        ];

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);
            foreach ($patterns as $pattern) {
                self::assertSame(
                    0,
                    preg_match($pattern, $contents),
                    "Deprecated or removed API found in {$file}: {$pattern}"
                );
            }
        }
    }

    public function testApplicationDoesNotDependOnShortOpenTags(): void
    {
        $files = $this->phpFiles();
        self::assertNotEmpty($files);

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);
            self::assertSame(
                0,
                preg_match('/^[ \t]*<\?(?!php|=)/mi', $contents),
                "Short open tag found in {$file}"
            );
        }
    }

    public function testDockerfileTargetsPhp83(): void
    {
        $dockerfile = (string) file_get_contents(self::ROOT . '/Dockerfile');
        self::assertMatchesRegularExpression('/^FROM\\s+php:8\\.3(?:[.-]|$)/mi', $dockerfile);
    }

    /** @return list<string> */
    private function phpFiles(): array
    {
        $root = realpath(self::ROOT);
        if ($root === false || !is_dir($root)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $root,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO
            )
        );
        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getRealPath();
            if ($path === false) {
                continue;
            }

            $relativePath = ltrim(substr($path, strlen($root)), DIRECTORY_SEPARATOR);
            $segments = explode(DIRECTORY_SEPARATOR, $relativePath);
            if (array_intersect($segments, ['vendor', 'tests', 'tools']) !== []) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);
        return $files;
    }
}
