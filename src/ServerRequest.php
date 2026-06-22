<?php

declare(strict_types=1);

namespace PhpPico\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest implements ServerRequestInterface
{
    use RequestTrait;

    /** @var array<array-key, mixed> */
    protected array $cookieParams = [];

    /** @var array<array-key, mixed> */
    protected array $queryParams = [];

    /** @var array<array-key, mixed> */
    protected array $uploadedFiles = [];

    protected array|object|null $parsedBody = null;

    /** @var array<array-key, mixed> */
    protected array $attributes = [];

    /** @param array<array-key, mixed> $serverParams */
    public function __construct(
        string $method = 'GET',
        ?UriInterface $uri = null,
        protected array $serverParams = [],
    ) {
        $this->method = $this->filterMethod($method);
        $this->uri = $uri ?? new Uri();

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }
    }

    #[\Override]
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    #[\Override]
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    #[\Override]
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    #[\Override]
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    #[\Override]
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    #[\Override]
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    #[\Override]
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $this->assertUploadedFiles($uploadedFiles);

        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    #[\Override]
    public function getParsedBody(): array|object|null
    {
        return $this->parsedBody;
    }

    #[\Override]
    public function withParsedBody($data): ServerRequestInterface
    {
        $new = clone $this;
        $new->parsedBody = $this->filterParsedBody($data);

        return $new;
    }

    protected function filterParsedBody(mixed $data): array|object|null
    {
        if ($data === null || is_array($data) || is_object($data)) {
            return $data;
        }

        throw new InvalidArgumentException('Parsed body must be an array, object, or null.');
    }

    #[\Override]
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    #[\Override]
    public function getAttribute(string $name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    #[\Override]
    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    #[\Override]
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /** @param array<array-key, mixed> $files */
    protected function assertUploadedFiles(array $files): void
    {
        // @mago-expect analysis:mixed-assignment -- the uploaded-files tree is mixed and validated below.
        foreach ($files as $file) {
            if (is_array($file)) {
                $this->assertUploadedFiles($file);
            } elseif (!$file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException('Uploaded files must be UploadedFileInterface instances.');
            }
        }
    }
}
