<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../prescia/lib/csrf.php';

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    public function testTokenIsRandomAndStoredInSession(): void
    {
        $token = \presciaCsrfToken();

        self::assertSame(64, strlen($token));
        self::assertSame($token, $_SESSION['prescia_csrf_token']);
        self::assertSame($token, \presciaCsrfToken());
    }

    public function testTokenIsInjectedIntoEveryForm(): void
    {
        $html = '<form method="post"><input name="name"></form><form action="/save"></form>';

        $result = \presciaInjectCsrfFields($html);

        self::assertSame(2, substr_count($result, 'name="csrf_token"'));
        self::assertStringContainsString('value="' . $_SESSION['prescia_csrf_token'] . '"', $result);
    }

    public function testAllMutatingMethodsRequireProtection(): void
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;
            self::assertTrue(\presciaRequestIsMutating(), $method);
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::assertFalse(\presciaRequestIsMutating());
    }

    public function testHeaderTokenIsAcceptedForApiRequests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $token = \presciaCsrfToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        self::assertSame($token, \presciaSubmittedCsrfToken());
        \presciaValidateCsrf();
    }

    public function testTokenRotationInvalidatesPreviousValue(): void
    {
        $oldToken = \presciaCsrfToken();
        $newToken = \presciaRotateCsrfToken();

        self::assertNotSame($oldToken, $newToken);
        self::assertSame($newToken, $_SESSION['prescia_csrf_token']);
    }
}
