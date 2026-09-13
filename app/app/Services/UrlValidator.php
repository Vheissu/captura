<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidUrlException;

class UrlValidator
{
    public function validate(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException('Invalid URL format');
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidUrlException('Only HTTP and HTTPS URLs are allowed');
        }

        $host = strtolower($parsed['host'] ?? '');

        // parse_url keeps the brackets around IPv6 literals (e.g. "[::1]").
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        $allowLocalhost = (bool) config('screenshot.security.allow_localhost', false);
        $localHosts = ['localhost', '127.0.0.1', '::1', 'host.docker.internal', 'host.containers.internal', 'nginx'];

        if ($allowLocalhost && in_array($host, $localHosts, true)) {
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

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isPrivateIp($host)) {
                throw new InvalidUrlException('URLs resolving to private IPs are not allowed');
            }

            return;
        }

        foreach ($this->resolveHostIps($host) as $ip) {
            if ($this->isPrivateIp($ip)) {
                throw new InvalidUrlException('URLs resolving to private IPs are not allowed');
            }
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

    /**
     * @return array<int, string>
     */
    private function resolveHostIps(string $host): array
    {
        $ips = [];

        $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];
        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if ($ips === []) {
            $ip = gethostbyname($host);
            if ($ip !== $host) {
                $ips[] = $ip;
            }
        }

        return $ips;
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
