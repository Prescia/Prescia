<?php
/*--------------------------------\
  | loadURL : Loads a remote URL using a socket, returns an array with header and content
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
-*/

if (!defined('PRESCIA_LOADURL_MAX_BYTES')) define('PRESCIA_LOADURL_MAX_BYTES', 2097152);
if (!defined('PRESCIA_LOADURL_MAX_DNS_RECORDS')) define('PRESCIA_LOADURL_MAX_DNS_RECORDS', 32);
if (!defined('PRESCIA_LOADURL_MAX_IPS')) define('PRESCIA_LOADURL_MAX_IPS', 16);

/** Return true only for globally routable IP addresses. */
function presciaLoadUrlIsPublicIp(string $ip): bool {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function presciaLoadUrlIsValidHost(string $host): bool {
    if ($host === '' || strlen($host) > 253 || strpbrk($host, "\r\n\0") !== false) return false;
    if (filter_var($host, FILTER_VALIDATE_IP)) return true;
    if (strpos($host, '.') === false || preg_match('/^[0-9.]+$/', $host)) return false;
    if (!preg_match('/^(?=.{1,253}\.?$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.?$/i', $host)) return false;
    return strtolower(rtrim($host, '.')) !== 'localhost';
}

/** Emit a redacted, low-cardinality security event without affecting the request result. */
function presciaLoadUrlLogEvent(string $reason, string $scheme, string $host, int $port, string $method): void {
    $safeHost = strtolower(trim($host));
    $safeHost = preg_replace('/[^a-z0-9.:%_-]/i', '', $safeHost);
    if (!is_string($safeHost)) $safeHost = '';
    $safeHost = substr($safeHost, 0, 253);
    $event = array(
        'schema' => 'prescia.security.v1',
        'event' => 'ssrf_blocked',
        'reason' => $reason,
        'scheme' => $scheme,
        'host' => $safeHost,
        'port' => $port > 0 && $port <= 65535 ? $port : null,
        'method' => $method,
        'timestamp' => gmdate('c'),
    );
    try {
        $encoded = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        error_log($encoded);
    } catch (Throwable $exception) {
        // Logging must never change a fail-closed SSRF decision.
    }
}

/** @param callable|null $resolver @return list<string> */
function presciaLoadUrlResolvePublicIps(string $host, ?callable $resolver = null): array {
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return presciaLoadUrlIsPublicIp($host) ? array($host) : array();
    }

    $ips = array();
    $records = $resolver !== null ? $resolver($host) : (function_exists('dns_get_record') ? @dns_get_record($host, DNS_A | DNS_AAAA) : false);
    if (is_array($records)) {
        if (count($records) > PRESCIA_LOADURL_MAX_DNS_RECORDS) return array();
        foreach ($records as $record) {
            $ip = isset($record['ip']) ? $record['ip'] : (isset($record['ipv6']) ? $record['ipv6'] : '');
            if ($ip !== '' && !presciaLoadUrlIsPublicIp($ip)) return array();
            if ($ip !== '') {
                $ips[] = $ip;
                if (count($ips) > PRESCIA_LOADURL_MAX_IPS) return array();
            }
        }
    }
    // Do not fall back to the legacy IPv4-only resolver: it can create a
    // second, differently scoped DNS decision than dns_get_record.
    if (!$ips) return array();
    return array_values(array_unique($ips));
}

/**
 * Open a connection using only the already validated IP list.
 *
 * @param list<string> $ips
 * @return resource|false
 */
function presciaLoadUrlOpenValidatedConnection(array $ips, string $scheme, int $port, $context, ?callable $connector = null) {
    if ($connector === null) {
        $connector = static function (string $target, int $targetPort, $targetContext) {
            return @stream_socket_client($target . ':' . $targetPort, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $targetContext);
        };
    }
    foreach ($ips as $ip) {
        if (!presciaLoadUrlIsPublicIp($ip)) continue;
        $connectHost = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $ip . ']' : $ip;
        $target = ($scheme === 'https' ? 'ssl://' : '') . $connectHost;
        $connection = $connector($target, $port, $context);
        if (is_resource($connection)) return $connection;
    }
    return false;
}

/**
 * Load a remote HTTP(S) URL without following redirects.
 * Every destination is validated independently to prevent SSRF and rebinding.
 *
 * @return array{0: list<string>, 1: string}|false
 */
function loadURL($url, $agent = 'PHP', $method = 'get') {
    $url = is_string($url) ? trim($url) : '';
    $fail = static function (string $reason, string $scheme = '', string $host = '', int $port = 0, string $method = 'get'): bool {
        presciaLoadUrlLogEvent($reason, $scheme, $host, $port, $method);
        return false;
    };
    if ($url === '' || strpbrk($url, "\r\n") !== false) return $fail('invalid_url');
    try {
        $parts = $url !== '' ? parse_url($url) : false;
    } catch (ValueError $exception) {
        return $fail('invalid_url');
    }
    $scheme = is_array($parts) && isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
    $host = is_array($parts) && isset($parts['host']) ? strtolower($parts['host']) : '';
    $method = strtolower((string) $method);
    if (!is_array($parts) || !in_array($scheme, array('http', 'https'), true) || !presciaLoadUrlIsValidHost($host) ||
        isset($parts['user']) || isset($parts['pass']) || !in_array($method, array('get', 'post'), true)) return $fail('invalid_request', $scheme, $host, 0, $method);

    $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
    if ($port < 1 || $port > 65535 || ($scheme === 'https' && $port !== 443) || ($scheme === 'http' && $port !== 80)) return $fail('invalid_port', $scheme, $host, $port, $method);
    $ips = presciaLoadUrlResolvePublicIps($host);
    if (!$ips) return $fail('private_or_unresolved_address', $scheme, $host, $port, $method);

    $path = isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';
    $query = isset($parts['query']) ? $parts['query'] : '';
    if ($method === 'get' && $query !== '') $path .= '?' . $query;
    $context = stream_context_create(array('ssl' => array(
        'verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host, 'SNI_enabled' => true,
    )));
    $fp = presciaLoadUrlOpenValidatedConnection($ips, $scheme, $port, $context);
    if (!is_resource($fp)) return $fail('connection_failed', $scheme, $host, $port, $method);
    stream_set_timeout($fp, 10);

    $safeAgent = preg_replace('/[\r\n]+/', ' ', (string) $agent);
    $request = ($method === 'post' && $query !== '' ? 'POST' : 'GET') . " $path HTTP/1.0\r\n";
    $request .= 'Host: ' . $host . "\r\nAccept: text/*\r\nUser-Agent: " . $safeAgent . "\r\nConnection: Close\r\n";
    if ($method === 'post' && $query !== '') {
        $request .= "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($query) . "\r\n\r\n" . $query;
    } else {
        $request .= "\r\n";
    }
    if (@fwrite($fp, $request) === false) { fclose($fp); return $fail('write_failed', $scheme, $host, $port, $method); }

    $response = '';
    while (!feof($fp) && strlen($response) <= PRESCIA_LOADURL_MAX_BYTES + 65536) {
        $chunk = fgets($fp, 8192);
        if ($chunk === false) break;
        $response .= $chunk;
    }
    fclose($fp);
    if (strlen($response) > PRESCIA_LOADURL_MAX_BYTES + 65536) return $fail('response_too_large', $scheme, $host, $port, $method);
    $parts = explode("\r\n\r\n", $response, 2);
    $body = isset($parts[1]) ? $parts[1] : '';
    if (strlen($body) > PRESCIA_LOADURL_MAX_BYTES) return $fail('body_too_large', $scheme, $host, $port, $method);
    return array(explode("\r\n", $parts[0]), $body);
}

function fget($url,$login,$pass,$file,$tries=1,$tmpfile="",$mode=FTP_ASCII) {
	$url = is_string($url) ? trim($url) : '';
	$parts = $url !== '' ? parse_url(strpos($url, '://') === false ? 'ftp://' . $url : $url) : false;
	$scheme = is_array($parts) && isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
	$host = is_array($parts) && isset($parts['host']) ? strtolower($parts['host']) : '';
	$port = is_array($parts) && isset($parts['port']) ? (int)$parts['port'] : 21;
	if ($scheme !== 'ftp' || !presciaLoadUrlIsValidHost($host) || $port < 1 || $port > 65535) return false;
	$ips = presciaLoadUrlResolvePublicIps($host);
	if (!$ips) return false;
	$tries = max(1, min((int)$tries, 3));
	if ($tmpfile == "") $tmpfile = "tmpdlw.tmp";
	if (is_file($tmpfile)) @unlink($tmpfile);
	while ($tries>0) {
		$fp = false;
		foreach ($ips as $ip) {
			$fp = @ftp_connect($ip, $port, 10);
			if ($fp) break;
		}
        if ($fp) {
            $login = ftp_login($fp, $login, $pass);
            if ($login) {
                ftp_pasv($fp,true); $handle = fopen($tmpfile, 'w');
                $sucess = ftp_fget($fp,$handle,$file,$mode);
                if ($sucess) { ftp_close($fp); fclose($handle); return cReadFile($tmpfile); }
                fclose($handle); if (is_file($tmpfile)) @unlink($tmpfile);
            }
            ftp_close($fp); unset($fp); $tries--; if ($tries>0) sleep(1);
        }
    }
    return false;
}

function getYoutubeViews($code,$simple=true) {
    $html = loadURL('https://www.youtube.com/watch?v='.$code);
    if ($html !== false) {
        $html = $html[1]; $haswvc = strpos($html,'"watch-view-count"',5000);
        if ($haswvc > 0) {
            $initpos = strpos($html,">",$haswvc); $endpos = strpos($html,"<",$initpos);
            $return = array(str_replace(".","",str_replace(",","",substr($html,$initpos+1,$endpos-$initpos-1))),'');
            if (!$simple) {
                $haswvc = strpos($html,'"datePublished"',100);
                if ($haswvc > 0) { $initpos = strpos($html,"content=",$haswvc)+9; $endpos = strpos($html,'"',$initpos); $return[1] = substr($html,$initpos,$endpos-$initpos); if (strlen($return[1])>10) $return[1] = ""; }
                else return "getYoutubeviews: publish date not found";
            }
            return $return;
        }
        return "getYoutubeviews:counter not found";
    }
    return "getYoutubeviews:loadURL fail";
}
?>
