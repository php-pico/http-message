<?php

declare(strict_types=1);

namespace PhpPico\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class UploadedFile implements UploadedFileInterface
{
    protected bool $moved = false;

    public function __construct(
        protected StreamInterface $stream,
        protected ?int $size,
        protected int $error,
        protected ?string $clientFilename = null,
        protected ?string $clientMediaType = null,
    ) {}

    #[\Override]
    public function getStream(): StreamInterface
    {
        $this->assertActive();

        return $this->stream;
    }

    #[\Override]
    public function moveTo(string $targetPath): void
    {
        $this->assertActive();

        if ($targetPath === '') {
            throw new RuntimeException('Target path must not be empty.');
        }

        PHP_SAPI === 'cli' ? $this->copyToTarget($targetPath) : $this->moveUploadedFile($targetPath);

        $this->moved = true;
    }

    #[\Override]
    public function getSize(): ?int
    {
        return $this->size;
    }

    #[\Override]
    public function getError(): int
    {
        return $this->error;
    }

    #[\Override]
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    #[\Override]
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    protected function assertActive(): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot access an uploaded file with a non-OK error status.');
        }

        if ($this->moved) {
            throw new RuntimeException('The uploaded file has already been moved.');
        }
    }

    protected function copyToTarget(string $targetPath): void
    {
        $target = fopen($targetPath, 'w');

        if ($target === false) {
            throw new RuntimeException("Unable to open target path: {$targetPath}");
        }

        $this->stream->rewind();

        while (!$this->stream->eof()) {
            fwrite($target, $this->stream->read(8192));
        }

        fclose($target);
    }

    protected function moveUploadedFile(string $targetPath): void
    {
        $source = $this->stream->getMetadata('uri');

        if (!is_string($source) || !move_uploaded_file($source, $targetPath)) {
            throw new RuntimeException("Unable to move uploaded file to: {$targetPath}");
        }
    }
}
