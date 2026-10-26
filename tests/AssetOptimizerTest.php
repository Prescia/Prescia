<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../prescia/lib/assetOptimizer.php';

final class AssetOptimizerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/prescia-assets-' . bin2hex(random_bytes(6));
        mkdir($this->directory . '/assets/nested', 0700, true);
        mkdir($this->directory . '/assets-sibling', 0700);
        file_put_contents($this->directory . '/assets/nested/app.js', 'window.safe = true;');
        file_put_contents($this->directory . '/assets/site.css', 'body {color: black}');
        file_put_contents($this->directory . '/assets/secret.php', '<?php // secret');
        file_put_contents($this->directory . '/assets-sibling/secret.js', 'secret');
        symlink($this->directory . '/assets-sibling/secret.js', $this->directory . '/assets/escape.js');
        symlink($this->directory . '/assets/secret.php', $this->directory . '/assets/source.js');
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir() && !$entry->isLink()) rmdir($entry->getPathname());
            else unlink($entry->getPathname());
        }
        rmdir($this->directory);
    }

    public function testValidNestedAssetsAndJqueryAlias(): void
    {
        $root = $this->directory . '/assets';
        self::assertSame(realpath($root . '/nested/app.js'), \presciaResolveAsset($root, 'nested/app.js', 'js'));
        self::assertSame(realpath($root . '/site.css'), \presciaResolveAsset($root, 'site.css', 'css'));
        $actual = realpath(__DIR__ . '/../pages/_js');
        self::assertNotFalse($actual);
        self::assertSame($actual . '/jquery-3.7.1.min.js', \presciaResolveAsset($actual, 'jquery.js', 'js'));
    }

    public function testUntrustedPathsAndTypesAreRejected(): void
    {
        $root = $this->directory . '/assets';
        foreach ([null, [], 42, '/etc/passwd', $root . '/nested/app.js', '../assets-sibling/secret.js',
            'nested/../../assets-sibling/secret.js', 'nested/../site.css', 'secret.php', 'site.css',
            'escape.js', 'source.js', 'file:///etc/passwd', 'nested\\app.js', "nested/app.js\0",
            '%2e%2e/secret.js', '<script>alert(1)</script>.js'] as $path) {
            self::assertNull(\presciaResolveAsset($root, $path, 'js'), var_export($path, true));
        }
        self::assertNull(\presciaResolveAsset($root, 'secret.php', 'php'));
    }
}
