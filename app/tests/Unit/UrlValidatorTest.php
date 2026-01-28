<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\InvalidUrlException;
use App\Services\UrlValidator;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UrlValidatorTest extends TestCase
{
    public function test_rejects_invalid_format(): void
    {
        $validator = new UrlValidator();

        $this->expectException(InvalidUrlException::class);
        $validator->validate('not-a-url');
    }

    public function test_rejects_non_http_scheme(): void
    {
        $validator = new UrlValidator();

        $this->expectException(InvalidUrlException::class);
        $validator->validate('ftp://example.com');
    }

    public function test_rejects_blocked_host(): void
    {
        Config::set('screenshot.security.blocked_hosts', ['localhost']);
        Config::set('screenshot.security.allow_localhost', false);
        $validator = new UrlValidator();

        $this->expectException(InvalidUrlException::class);
        $validator->validate('http://localhost');
    }

    public function test_allows_localhost_when_enabled(): void
    {
        Config::set('screenshot.security.blocked_hosts', ['localhost']);
        Config::set('screenshot.security.allow_localhost', true);
        $validator = new UrlValidator();

        $validator->validate('http://localhost');
        $validator->validate('http://nginx');

        $this->addToAssertionCount(2);
    }

    public function test_allows_host_in_allow_list(): void
    {
        Config::set('screenshot.security.blocked_hosts', ['localhost']);
        Config::set('screenshot.security.allowed_hosts', ['localhost']);
        $validator = new UrlValidator();

        $validator->validate('http://localhost');

        $this->addToAssertionCount(1);
    }
}
