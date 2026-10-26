<?php
declare(strict_types=1);

/**
 * Small, dependency-free login limiter for deployments without shared cache.
 * The key is hashed so account names and IP addresses are not stored in files.
 * Multi-node deployments should replace this storage with a shared backend.
 */
final class PresciaLoginRateLimiter
{
    private const MAX_FAILURES = 5;
    private const WINDOW_SECONDS = 900;
    private const BASE_BLOCK_SECONDS = 60;
    private const MAX_BLOCK_SECONDS = 3600;

    public function __construct(
        private readonly string $directory,
        private readonly int $maxFailures = self::MAX_FAILURES,
        private readonly int $windowSeconds = self::WINDOW_SECONDS
    ) {
        if ($this->maxFailures < 1 || $this->windowSeconds < 1) {
            throw new InvalidArgumentException('Login limiter thresholds must be positive.');
        }
    }

    public function isAllowed(string $login, string $ip, ?int $now = null): bool
    {
        $state = $this->readState($this->key($login, $ip));
        $timestamp = $now ?? time();
        return !isset($state['blocked_until']) || (int)$state['blocked_until'] <= $timestamp;
    }

    public function recordFailure(string $login, string $ip, ?int $now = null): void
    {
        $timestamp = $now ?? time();
        $key = $this->key($login, $ip);
        $lock = $this->lock($key);
        if ($lock === false) return;
        try {
            $state = $this->readState($key);
            $cutoff = $timestamp - $this->windowSeconds;
            $failures = array_values(array_filter(
                is_array($state['failures'] ?? null) ? $state['failures'] : [],
                static fn ($failure): bool => is_int($failure) && $failure >= $cutoff
            ));
            $failures[] = $timestamp;
            $state = ['failures' => $failures];
            if (count($failures) >= $this->maxFailures) {
                $excess = count($failures) - $this->maxFailures;
                $state['blocked_until'] = $timestamp + min(
                    self::MAX_BLOCK_SECONDS,
                    self::BASE_BLOCK_SECONDS * (2 ** min($excess, 6))
                );
            }
            $this->writeState($key, $state);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function clear(string $login, string $ip): void
    {
        $key = $this->key($login, $ip);
        $lock = $this->lock($key);
        if ($lock === false) return;
        try {
            $path = $this->path($key);
            if (is_file($path)) @unlink($path);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function key(string $login, string $ip): string
    {
        return hash('sha256', strtolower(trim($login)) . "\0" . trim($ip));
    }

    private function path(string $key): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.json';
    }

    /** @return resource|false */
    private function lock(string $key)
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            return false;
        }
        $handle = @fopen($this->path($key) . '.lock', 'c');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) fclose($handle);
            return false;
        }
        @chmod($this->path($key) . '.lock', 0600);
        return $handle;
    }

    /** @return array{failures?: list<int>, blocked_until?: int} */
    private function readState(string $key): array
    {
        $raw = @file_get_contents($this->path($key));
        $state = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($state) ? $state : [];
    }

    /** @param array{failures: list<int>, blocked_until?: int} $state */
    private function writeState(string $key, array $state): void
    {
        $path = $this->path($key);
        $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $encoded = json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if (@file_put_contents($temporary, $encoded, LOCK_EX) === false) return;
        @chmod($temporary, 0600);
        if (!@rename($temporary, $path)) @unlink($temporary);
        @chmod($path, 0600);
    }
}
