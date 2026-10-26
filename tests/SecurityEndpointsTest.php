<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

/** Execute the real entrypoints in a child process so exit() cannot hide a mutation. */
final class SecurityEndpointsTest extends TestCase
{
    /** @return array{status: int, body: string, mutations: array} */
    private function request(string $endpoint, string $method, array $get = [], array $post = [], array $session = []): array
    {
        $setup = '$_SERVER["REQUEST_METHOD"] = ' . var_export($method, true) . ';'
            . '$_GET = ' . var_export($get, true) . ';'
            . '$_POST = ' . var_export($post, true) . ';'
            . '$_REQUEST = array_merge($_GET, $_POST);'
            . '$_SESSION = ' . var_export($session, true) . ';';
        $code = $setup . <<<'PHP'
ob_start();
$mutations = [];
register_shutdown_function(static function () use (&$mutations): void {
    $body = (string) ob_get_clean();
    echo json_encode(['status' => http_response_code() ?: 200, 'body' => $body, 'mutations' => $mutations], JSON_THROW_ON_ERROR);
});
define('CONS_ACTION_DELETE', 3);
define('CONS_LOGGING_SUCCESS', 1);
$core = new class($mutations) {
    public array $mutations;
    public object $headerControl;
    public array $log = [];
    public string $action = '';
    public function __construct(array &$mutations) {
        $this->mutations = &$mutations;
        $this->headerControl = new class {
            public function internalFoward(string $url): void {}
        };
    }
    public function loaded(string $name): object|false {
        return $name === 'items' ? (object)['name' => $name] : false;
    }
    public function runAction(object $module, int $action, array $data): bool {
        $this->mutations[] = $data;
        return true;
    }
    public function setLog(int $level, string $message): void {}
    public function langOut(string $key): string { return $key; }
};
PHP;
        $code .= 'require ' . var_export(__DIR__ . '/../prescia/lib/csrf.php', true) . ';'
            . 'require ' . var_export(__DIR__ . '/../' . $endpoint, true) . ';';
        $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), (string)$errors);
        self::assertSame('', $errors);
        return json_decode((string)$output, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testDeleteRejectsGetAndOtherMethodsBeforeMutation(): void
    {
        foreach (['GET', 'HEAD', 'PUT', 'DELETE'] as $method) {
            $result = $this->request('prescia/plugins/bi_adm/payload/actions/delete.php', $method,
                ['module' => 'items', 'id' => '7'], ['module' => 'items', 'id' => '7', 'csrf_token' => 'valid'],
                ['prescia_csrf_token' => 'valid']);
            self::assertSame(405, $result['status']);
            self::assertSame([], $result['mutations']);
        }
    }

    public function testDeleteRejectsMissingInvalidAndOldTokensBeforeMutation(): void
    {
        foreach ([null, '', 'invalid', 'previous-token', ['valid']] as $token) {
            $post = ['module' => 'items', 'id' => '7'];
            if ($token !== null) $post['csrf_token'] = $token;
            $result = $this->request('prescia/plugins/bi_adm/payload/actions/delete.php', 'POST', [], $post,
                ['prescia_csrf_token' => 'valid']);
            self::assertSame(403, $result['status']);
            self::assertSame([], $result['mutations']);
        }
    }

    public function testDeleteAcceptsValidPostAndIgnoresQueryKeys(): void
    {
        $post = ['module' => 'items', 'id' => '7', 'csrf_token' => 'valid'];
        $result = $this->request('prescia/plugins/bi_adm/payload/actions/delete.php', 'POST',
            ['module' => 'other', 'id' => '999'], $post, ['prescia_csrf_token' => 'valid']);
        self::assertSame(200, $result['status']);
        self::assertSame([$post], $result['mutations']);
        $result = $this->request('prescia/plugins/bi_adm/payload/actions/delete.php', 'POST',
            ['module' => 'items', 'id' => '999'], ['csrf_token' => 'valid'], ['prescia_csrf_token' => 'valid']);
        self::assertSame([], $result['mutations']);
    }

    public function testOptimizerDoesNotDiscloseFilesOrReflectPayloads(): void
    {
        foreach ([['js' => '/etc/passwd'], ['css' => '/etc/passwd'], ['js' => '../../config/settings.php'],
            ['js' => ['common.js']], ['js' => '<script>alert(1)</script>']] as $get) {
            $result = $this->request('pages/_js/optimizer.php', 'GET', $get);
            self::assertSame(404, $result['status']);
            self::assertSame('', $result['body']);
        }
        $result = $this->request('pages/_js/optimizer.php', 'POST', [], ['js' => '/etc/passwd']);
        self::assertSame(405, $result['status']);
    }

    public function testOptimizerServesJavascriptCssAndHead(): void
    {
        foreach ([['js' => 'common.js'], ['js' => 'jquery.js'], ['css' => 'bootstrap/css/bootstrap.min.css']] as $get) {
            $result = $this->request('pages/_js/optimizer.php', 'GET', $get);
            self::assertSame(200, $result['status']);
            self::assertNotSame('', $result['body']);
        }
        $result = $this->request('pages/_js/optimizer.php', 'HEAD', ['js' => 'common.js']);
        self::assertSame(200, $result['status']);
        self::assertSame('', $result['body']);
    }
}
