<?php

declare(strict_types=1);

namespace PhpPico\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

final class Uri implements UriInterface
{
    protected const DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'ftps' => 990,
        'ssh' => 22,
        'sftp' => 22,
        'smtp' => 25,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
        'ws' => 80,
        'wss' => 443,
    ];

    protected const UNRESERVED = 'a-zA-Z0-9\-._~';
    protected const SUB_DELIMS = '!\$&\'()*+,;=';

    protected string $scheme = '';
    protected string $userInfo = '';
    protected string $host = '';
    protected ?int $port = null;
    protected string $path = '';
    protected string $query = '';
    protected string $fragment = '';

    public function __construct(string $uri = '')
    {
        if ($uri === '') {
            return;
        }

        $parts = parse_url($uri);

        if ($parts === false) {
            throw new InvalidArgumentException("Unable to parse URI: {$uri}");
        }

        $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port = $this->filterPort($parts['port'] ?? null);
        $this->path = isset($parts['path']) ? $this->encodePath($parts['path']) : '';
        $this->query = isset($parts['query']) ? $this->encodeQueryOrFragment($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? $this->encodeQueryOrFragment($parts['fragment']) : '';
        $this->userInfo = $this->buildUserInfo($parts['user'] ?? '', $parts['pass'] ?? null);
    }

    #[\Override]
    public function getScheme(): string
    {
        return $this->scheme;
    }

    #[\Override]
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = "{$this->userInfo}@{$authority}";
        }

        $port = $this->getPort();

        if ($port !== null) {
            $authority = "{$authority}:{$port}";
        }

        return $authority;
    }

    #[\Override]
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    #[\Override]
    public function getHost(): string
    {
        return $this->host;
    }

    #[\Override]
    public function getPort(): ?int
    {
        if ($this->port === null) {
            return null;
        }

        return (self::DEFAULT_PORTS[$this->scheme] ?? null) === $this->port ? null : $this->port;
    }

    #[\Override]
    public function getPath(): string
    {
        return $this->path;
    }

    #[\Override]
    public function getQuery(): string
    {
        return $this->query;
    }

    #[\Override]
    public function getFragment(): string
    {
        return $this->fragment;
    }

    #[\Override]
    public function withScheme(string $scheme): UriInterface
    {
        $new = clone $this;
        $new->scheme = strtolower($scheme);

        return $new;
    }

    #[\Override]
    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $new = clone $this;
        $new->userInfo = $this->buildUserInfo($user, $password);

        return $new;
    }

    #[\Override]
    public function withHost(string $host): UriInterface
    {
        $new = clone $this;
        $new->host = $this->filterHost($host);

        return $new;
    }

    #[\Override]
    public function withPort(?int $port): UriInterface
    {
        $new = clone $this;
        $new->port = $this->filterPort($port);

        return $new;
    }

    #[\Override]
    public function withPath(string $path): UriInterface
    {
        $new = clone $this;
        $new->path = $this->encodePath($path);

        return $new;
    }

    #[\Override]
    public function withQuery(string $query): UriInterface
    {
        $new = clone $this;
        $new->query = $this->encodeQueryOrFragment($query);

        return $new;
    }

    #[\Override]
    public function withFragment(string $fragment): UriInterface
    {
        $new = clone $this;
        $new->fragment = $this->encodeQueryOrFragment($fragment);

        return $new;
    }

    #[\Override]
    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= "{$this->scheme}:";
        }

        $authority = $this->getAuthority();

        if ($authority !== '') {
            $uri .= "//{$authority}";
        }

        $uri .= $this->normalizePathForString($authority);

        if ($this->query !== '') {
            $uri .= "?{$this->query}";
        }

        if ($this->fragment !== '') {
            $uri .= "#{$this->fragment}";
        }

        return $uri;
    }

    protected function normalizePathForString(string $authority): string
    {
        $path = $this->path;

        if ($authority !== '' && $path !== '' && $path[0] !== '/') {
            return "/{$path}";
        }

        if ($authority === '' && str_starts_with($path, '//')) {
            return '/' . ltrim($path, '/');
        }

        return $path;
    }

    protected function buildUserInfo(string $user, ?string $password): string
    {
        if ($user === '') {
            return '';
        }

        $userInfo = $this->encodeUserInfo($user);

        if ($password !== null && $password !== '') {
            $userInfo .= ':' . $this->encodeUserInfo($password);
        }

        return $userInfo;
    }

    protected function filterHost(string $host): string
    {
        if (preg_match('/[\r\n]/', $host) === 1) {
            throw new InvalidArgumentException('Host must not contain CR or LF characters.');
        }

        return strtolower($host);
    }

    protected function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException("Port {$port} is outside the valid range 1-65535.");
        }

        return $port;
    }

    protected function encodePath(string $path): string
    {
        return $this->encode($path, self::UNRESERVED . self::SUB_DELIMS . '%:@\/');
    }

    protected function encodeQueryOrFragment(string $value): string
    {
        return $this->encode($value, self::UNRESERVED . self::SUB_DELIMS . '%:@\/?');
    }

    protected function encodeUserInfo(string $value): string
    {
        return $this->encode($value, self::UNRESERVED . self::SUB_DELIMS . '%');
    }

    protected function encode(string $value, string $allowed): string
    {
        return (
            preg_replace_callback(
                '/(?:[^' . $allowed . ']|%(?![A-Fa-f0-9]{2}))/',
                static fn(array $match): string => rawurlencode($match[0]),
                $value,
            ) ?? $value
        );
    }
}
