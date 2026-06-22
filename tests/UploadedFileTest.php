<?php

declare(strict_types=1);

namespace PhpPico\Http\Message\Tests;

use PhpPico\Http\Message\Stream;
use PhpPico\Http\Message\UploadedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

#[CoversClass(UploadedFile::class)]
final class UploadedFileTest extends TestCase
{
    public function testExposesMetadata(): void
    {
        $file = new UploadedFile(Stream::create('data'), 4, UPLOAD_ERR_OK, 'name.txt', 'text/plain');

        $this->assertSame(4, $file->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
        $this->assertSame('name.txt', $file->getClientFilename());
        $this->assertSame('text/plain', $file->getClientMediaType());
    }

    public function testGetStreamReturnsTheStream(): void
    {
        $stream = Stream::create('data');
        $file = new UploadedFile($stream, 4, UPLOAD_ERR_OK);

        $this->assertInstanceOf(StreamInterface::class, $file->getStream());
        $this->assertSame('data', (string) $file->getStream());
    }

    public function testMoveToWritesContentsToTarget(): void
    {
        $target = sys_get_temp_dir() . '/uploaded_' . uniqid() . '.txt';
        $file = new UploadedFile(Stream::create('payload'), 7, UPLOAD_ERR_OK);

        $file->moveTo($target);

        $this->assertFileExists($target);
        $this->assertSame('payload', file_get_contents($target));

        unlink($target);
    }

    public function testMoveToCannotBeCalledTwice(): void
    {
        $target = sys_get_temp_dir() . '/uploaded_' . uniqid() . '.txt';
        $file = new UploadedFile(Stream::create('payload'), 7, UPLOAD_ERR_OK);
        $file->moveTo($target);
        unlink($target);

        $this->expectException(RuntimeException::class);

        $file->moveTo($target);
    }

    public function testGetStreamThrowsAfterMove(): void
    {
        $target = sys_get_temp_dir() . '/uploaded_' . uniqid() . '.txt';
        $file = new UploadedFile(Stream::create('payload'), 7, UPLOAD_ERR_OK);
        $file->moveTo($target);
        unlink($target);

        $this->expectException(RuntimeException::class);

        $file->getStream();
    }

    public function testGetStreamThrowsWhenErrorPresent(): void
    {
        $file = new UploadedFile(Stream::create(''), null, UPLOAD_ERR_NO_FILE);

        $this->expectException(RuntimeException::class);

        $file->getStream();
    }

    public function testMoveToThrowsWhenErrorPresent(): void
    {
        $file = new UploadedFile(Stream::create(''), null, UPLOAD_ERR_INI_SIZE);

        $this->expectException(RuntimeException::class);

        $file->moveTo(sys_get_temp_dir() . '/never.txt');
    }

    public function testMoveToRejectsEmptyTargetPath(): void
    {
        $file = new UploadedFile(Stream::create('x'), 1, UPLOAD_ERR_OK);

        $this->expectException(RuntimeException::class);

        $file->moveTo('');
    }

    public function testOptionalMetadataDefaultsToNull(): void
    {
        $file = new UploadedFile(Stream::create('x'), 1, UPLOAD_ERR_OK);

        $this->assertNull($file->getClientFilename());
        $this->assertNull($file->getClientMediaType());
    }
}
