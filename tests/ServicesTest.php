<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;
use Prescia\Services\FileService;
use Prescia\Services\Sanitizer;

final class ServicesTest extends TestCase
{
    public function testEscapesHtmlEntitiesAndInvalidUtf8(): void
    {
        self::assertSame('&lt;script&gt;&quot;x&quot;&lt;/script&gt;', Sanitizer::escapeHtml('<script>"x"</script>'));
        self::assertSame('', Sanitizer::text("\xB1"));
    }

    public function testStripsTagsAndOptionallyPreservesLineBreaks(): void
    {
        self::assertSame('hello world', Sanitizer::stripTags('<b>hello</b> world'));
        self::assertSame("hello<br/>\nworld", Sanitizer::stripTags("<b>hello</b>\nworld", true));
    }

    public function testSanitizeHtmlUsesAllowlistAndSafeLinkSchemes(): void
    {
        $html = Sanitizer::sanitizeHtml('<p>Hello <strong>world</strong><script>alert(1)</script><a href="javascript:alert(1)" onclick="alert(2)">link</a></p>');

        self::assertStringContainsString('<p>Hello <strong>world</strong>alert(1)', $html);
        self::assertStringContainsString('<a>link</a>', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('onclick', $html);
        self::assertStringNotContainsString('javascript:', $html);
    }

    public function testFileServiceWritesReadsAndCreatesDirectories(): void
    {
        $root = sys_get_temp_dir() . '/prescia-services-' . bin2hex(random_bytes(6));
        $path = $root . '/nested/cache.txt';
        $service = new FileService();
        try {
            $service->write($path, 'safe');
            self::assertSame('safe', $service->read($path));
            $service->write($path, '-append', true);
            self::assertSame('safe-append', $service->read($path));
            self::assertNull($service->read($root . '/missing.txt'));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            @rmdir($root . '/nested');
            @rmdir($root);
        }
    }
}
