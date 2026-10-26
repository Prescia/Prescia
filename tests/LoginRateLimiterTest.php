<?php
declare(strict_types=1);

namespace Prescia\Tests;

require_once __DIR__ . '/../prescia/lib/loginRateLimiter.php';
require_once __DIR__ . '/../prescia/lib/ipv6.php';

use PHPUnit\Framework\TestCase;
use PresciaLoginRateLimiter;

final class LoginRateLimiterTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/prescia-login-limiter-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->directory);
    }

    public function testBlocksAfterConfiguredFailuresAndUsesBackoff(): void
    {
        $limiter = new PresciaLoginRateLimiter($this->directory, 3, 900);

        self::assertTrue($limiter->isAllowed('Admin', '203.0.113.10', 1000));
        $limiter->recordFailure('Admin', '203.0.113.10', 1000);
        $limiter->recordFailure('Admin', '203.0.113.10', 1001);
        self::assertTrue($limiter->isAllowed('Admin', '203.0.113.10', 1002));
        $limiter->recordFailure('Admin', '203.0.113.10', 1002);

        self::assertFalse($limiter->isAllowed('admin', '203.0.113.10', 1003));
        self::assertTrue($limiter->isAllowed('admin', '203.0.113.10', 1062));
    }

    public function testFailuresExpireOutsideTheWindow(): void
    {
        $limiter = new PresciaLoginRateLimiter($this->directory, 2, 60);

        $limiter->recordFailure('admin', '203.0.113.10', 1000);
        $limiter->recordFailure('admin', '203.0.113.10', 1061);
        self::assertTrue($limiter->isAllowed('admin', '203.0.113.10', 1062));
    }

    public function testSuccessfulLoginCanClearTheKey(): void
    {
        $limiter = new PresciaLoginRateLimiter($this->directory, 1, 900);

        $limiter->recordFailure('admin', '203.0.113.10', 1000);
        self::assertFalse($limiter->isAllowed('admin', '203.0.113.10', 1001));
        $limiter->clear('admin', '203.0.113.10');
        self::assertTrue($limiter->isAllowed('admin', '203.0.113.10', 1001));
    }

    public function testAccountAndIpAreBothPartOfTheIsolationKey(): void
    {
        $limiter = new PresciaLoginRateLimiter($this->directory, 1, 900);

        $limiter->recordFailure('admin', '203.0.113.10', 1000);
        self::assertTrue($limiter->isAllowed('other', '203.0.113.10', 1001));
        self::assertTrue($limiter->isAllowed('admin', '203.0.113.11', 1001));
    }

    public function testSpoofedHeadersAndSessionCacheCannotResetTheLoginCounter(): void
    {
        $server = $_SERVER;
        $session = $_SESSION ?? [];
        $request = $_REQUEST;
        try {
            $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
            $_SESSION = [];
            $_REQUEST = [];
            $limiter = new PresciaLoginRateLimiter($this->directory, 2, 900);
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $_SERVER['HTTP_CLIENT_IP'] = '198.51.100.' . ($attempt + 1);
                $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.' . ($attempt + 1);
                $_SESSION['prescia_ipcache_remote'] = 'spoofed-cache';
                $limiter->recordFailure('admin', \GetIP(), 1000 + $attempt);
            }
            $_REQUEST['nocache'] = '1';
            self::assertSame(\IPv4To6('203.0.113.10', true), \GetIP());
            self::assertFalse($limiter->isAllowed('admin', \GetIP(), 1002));
            $_SERVER['REMOTE_ADDR'] = '2001:db8::1';
            self::assertSame(\IPv4To6('2001:db8::1', true), \GetIP());
            unset($_SERVER['REMOTE_ADDR']);
            self::assertSame(\IPv4To6('0.0.0.0', true), \GetIP());
        } finally {
            $_SERVER = $server;
            $_SESSION = $session;
            $_REQUEST = $request;
        }
    }
}
