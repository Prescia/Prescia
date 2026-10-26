<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

final class VendoredAssetsTest extends TestCase
{
    public function testVendoredScriptsMatchTheReviewedUpstreamAssets(): void
    {
        $manifest = json_decode((string)file_get_contents(__DIR__ . '/../docs/vendor-assets.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($manifest['assets'] as $asset) {
            foreach ($asset['files'] as $file) {
                $path = __DIR__ . '/../' . $file['path'];
                self::assertFileExists($path);
                self::assertSame($file['sha256'], hash_file('sha256', $path), $file['path']);
            }
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__ . '/../pages/_js', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry->isFile() || !preg_match('/^jquery(?:[.-].*)?\.js$/', $entry->getFilename())) continue;
            $content = (string)file_get_contents($entry->getPathname());
            if (preg_match('/jQuery v([0-9.]+)/', $content, $match)) {
                self::assertTrue(version_compare($match[1], '3.7.1', '>='), $entry->getPathname());
            }
        }
    }
}
