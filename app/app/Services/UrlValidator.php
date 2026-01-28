<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidUrlException;

class UrlValidator
{
    public function validate(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException('Invalid URL format');
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidUrlException('Only HTTP and HTTPS URLs are allowed');
        }

        $host = strtolower($parsed['host'] ?? '');
        $allowLocalhost = (bool) config('screenshot.security.allow_localhost', false);
        $localHosts = ['localhost', '127.0.0.1', '::1', 'host.docker.internal', 'host.containers.internal', 'nginx'];
        $isLocalhost = in_array($host, $localHosts, true);

        if ($allowLocalhost && $isLocalhost) {
            return;
        }

        foreach (config('screenshot.security.allowed_hosts') as $pattern) {
            if ($this->matchesPattern($host, $pattern)) {
                return;
            }
        }

        foreach (config('screenshot.security.blocked_hosts') as $pattern) {
            if ($this->matchesPattern($host, $pattern)) {
                throw new InvalidUrlException('This host is not allowed');
            }
        }

        $ip = gethostbyname($host);
        if ($ip !== $host && $this->isPrivateIp($ip)) {
            throw new InvalidUrlException('URLs resolving to private IPs are not allowed');
        }
    }

    private function matchesPattern(string $host, string $pattern): bool
    {
        $pattern = strtolower(trim($pattern));
        if ($pattern === '') {
            return false;
        }

        if (str_contains($pattern, '*')) {
            return fnmatch($pattern, $host, FNM_CASEFOLD);
        }

        return $host === $pattern;
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
