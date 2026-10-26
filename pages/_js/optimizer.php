<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/prescia/lib/assetOptimizer.php';

header('X-Content-Type-Options: nosniff');
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit;
}
$extension = isset($_GET['js']) ? 'js' : 'css';
$file = presciaResolveAsset(__DIR__, $_GET[$extension] ?? null, $extension);
$content = $file === null ? false : file_get_contents($file);
if ($content === false) {
    http_response_code(404);
    header('Cache-Control: no-store');
    exit;
}
header('Content-Type: ' . ($extension === 'js' ? 'text/javascript' : 'text/css') . '; charset=UTF-8');
header('Cache-Control: public, max-age=604800');
header('Vary: Accept-Encoding');
if ($method === 'HEAD') {
    exit;
}
// PHP/Apache can negotiate compression; gzip;q=0 must never force gzip output.
echo $content;
