<?php

declare(strict_types=1);

namespace PhpPico\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @require-implements RequestInterface
 */
trait RequestTrait
{
    use MessageTrait;

    protected string $method = 'GET';
    protected ?string $requestTarget = null;
    protected UriInterface $uri;

    protected function initialize(string $method, ?UriInterface $uri): void
    {
        $this->method = $this->filterMethod($method);
        $this->uri = $uri ?? new Uri();

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        $query = $this->uri->getQuery();

        if ($query !== '') {
            $target .= "?{$query}";
        }

        return $target === '' ? '/' : $target;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        if (preg_match('/\s/', $requestTarget) === 1) {
            throw new InvalidArgumentException('Request target must not contain whitespace.');
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $new = clone $this;
        $new->method = $this->filterMethod($method);

        return $new;
    }

    protected function filterMethod(string $method): string
    {
        if (preg_match(self::TOKEN, $method) !== 1) {
            throw new InvalidArgumentException("Invalid HTTP method: {$method}");
        }

        return $method;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $new->updateHostFromUri();
        }

        return $new;
    }

    protected function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();

        if ($host === '') {
            return;
        }

        $port = $this->uri->getPort();

        if ($port !== null) {
            $host .= ":{$port}";
        }

        $this->removeHeader('Host');
        $this->headerNames['host'] = 'Host';
        $this->headers['Host'] = [$host];
    }
}
