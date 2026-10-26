<?php

declare(strict_types=1);

namespace Prescia\Services;

use RuntimeException;

final class FileService
{
    public function read(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        $contents = file_get_contents($path);
        return $contents === false ? null : $contents;
    }

    public function write(string $path, string $contents, bool $append = false): void
    {
        $directory = dirname($path);
        $this->ensureDirectory($directory);
        $flags = $append ? FILE_APPEND : 0;
        if (file_put_contents($path, $contents, $flags) === false) {
            throw new RuntimeException('Unable to write file.');
        }
    }

    public function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }
        if (!mkdir($path, 0750, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create directory.');
        }
    }
}
