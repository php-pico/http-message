<?php

declare(strict_types=1);

namespace PhpPico\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class Stream implements StreamInterface
{
    protected const READABLE_MODE = '/r|\+/';
    protected const WRITABLE_MODE = '/[waxc]|\+/';

    /** @var resource|null */
    protected $resource;

    protected bool $seekable;
    protected bool $readable;
    protected bool $writable;

    /** @param resource $resource */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Stream resource must be a valid PHP resource.');
        }

        $this->resource = $resource;

        $meta = stream_get_meta_data($resource);
        $mode = $meta['mode'];

        $this->seekable = $meta['seekable'];
        $this->readable = preg_match(self::READABLE_MODE, $mode) === 1;
        $this->writable = preg_match(self::WRITABLE_MODE, $mode) === 1;
    }

    public static function create(StreamInterface|string $body = ''): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        $resource = fopen('php://temp', 'r+');

        if ($resource === false) {
            throw new RuntimeException('Unable to open a temporary stream.');
        }

        fwrite($resource, $body);
        rewind($resource);

        return new self($resource);
    }

    /** @return resource */
    protected function resource()
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached.');
        }

        return $this->resource;
    }

    #[\Override]
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (RuntimeException) {
            return '';
        }
    }

    #[\Override]
    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }

        $this->detach();
    }

    #[\Override]
    public function detach()
    {
        $resource = $this->resource;

        $this->resource = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;

        return $resource;
    }

    #[\Override]
    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        $size = fstat($this->resource)['size'] ?? null;

        return $size === null ? null : (int) $size;
    }

    #[\Override]
    public function tell(): int
    {
        $position = $this->resource === null ? false : ftell($this->resource);

        if ($position === false) {
            throw new RuntimeException('Unable to determine stream position.');
        }

        return $position;
    }

    #[\Override]
    public function eof(): bool
    {
        return $this->resource === null || feof($this->resource);
    }

    #[\Override]
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    #[\Override]
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->seekable || fseek($this->resource(), $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek within the stream.');
        }
    }

    #[\Override]
    public function rewind(): void
    {
        $this->seek(0);
    }

    #[\Override]
    public function isWritable(): bool
    {
        return $this->writable;
    }

    #[\Override]
    public function write(string $string): int
    {
        $bytes = $this->writable ? fwrite($this->resource(), $string) : false;

        if ($bytes === false) {
            throw new RuntimeException('Unable to write to the stream.');
        }

        return $bytes;
    }

    #[\Override]
    public function isReadable(): bool
    {
        return $this->readable;
    }

    #[\Override]
    public function read(int $length): string
    {
        $data = $this->readable ? fread($this->resource(), $length) : false;

        if ($data === false) {
            throw new RuntimeException('Unable to read from the stream.');
        }

        return $data;
    }

    #[\Override]
    public function getContents(): string
    {
        $contents = $this->readable ? stream_get_contents($this->resource()) : false;

        if ($contents === false) {
            throw new RuntimeException('Unable to read the stream contents.');
        }

        return $contents;
    }

    #[\Override]
    public function getMetadata(?string $key = null)
    {
        if ($this->resource === null) {
            return $key === null ? [] : null;
        }

        $meta = stream_get_meta_data($this->resource);

        // @mago-expect analysis:mixed-return-statement -- PSR-7 getMetadata returns mixed by contract.
        return $key === null ? $meta : $meta[$key] ?? null;
    }
}
