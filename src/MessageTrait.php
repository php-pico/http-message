<?php

declare(strict_types=1);

namespace PhpPico\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @require-implements MessageInterface
 */
trait MessageTrait
{
    protected const TOKEN = '/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/';
    protected const HEADER_VALUE = '/^[\x20\x09\x21-\x7E\x80-\xFF]*$/';

    protected string $protocolVersion = '1.1';

    /** @var array<string, list<string>> */
    protected array $headers = [];

    /** @var array<string, string> */
    protected array $headerNames = [];

    protected ?StreamInterface $body = null;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $new = clone $this;
        $new->protocolVersion = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $original = $this->headerNames[strtolower($name)] ?? null;

        return $original === null ? [] : $this->headers[$original];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $this->assertHeaderName($name);
        $values = $this->filterHeaderValue($value);

        $new = clone $this;
        $new->removeHeader($name);
        $new->headerNames[strtolower($name)] = $name;
        $new->headers[$name] = $values;

        return $new;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $this->assertHeaderName($name);
        $values = $this->filterHeaderValue($value);

        $new = clone $this;
        $original = $new->headerNames[strtolower($name)] ?? null;

        if ($original === null) {
            $new->headerNames[strtolower($name)] = $name;
            $new->headers[$name] = $values;
        } else {
            $new->headers[$original] = [...$new->headers[$original], ...$values];
        }

        return $new;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $new = clone $this;
        $new->removeHeader($name);

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body ??= Stream::create();
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    protected function removeHeader(string $name): void
    {
        $lower = strtolower($name);
        $original = $this->headerNames[$lower] ?? null;

        if ($original !== null) {
            unset($this->headers[$original], $this->headerNames[$lower]);
        }
    }

    /**
     * @return list<string>
     */
    protected function filterHeaderValue(mixed $value): array
    {
        $values = is_array($value) ? array_values($value) : [$value];

        if ($values === []) {
            throw new InvalidArgumentException('Header value must not be an empty array.');
        }

        $filtered = [];

        // @mago-expect analysis:mixed-assignment -- PSR-7 header values are mixed and validated below.
        foreach ($values as $line) {
            if (!is_string($line) || preg_match(self::HEADER_VALUE, $line) !== 1) {
                throw new InvalidArgumentException('Header value contains invalid characters.');
            }

            $filtered[] = $line;
        }

        return $filtered;
    }

    protected function assertHeaderName(string $name): void
    {
        if (preg_match(self::TOKEN, $name) !== 1) {
            throw new InvalidArgumentException("Invalid header name: {$name}");
        }
    }
}
