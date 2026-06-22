<?php

declare(strict_types=1);

namespace PhpPico\Http\Message\Tests;

use InvalidArgumentException;
use PhpPico\Http\Message\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

#[CoversClass(Stream::class)]
final class StreamTest extends TestCase
{
    public function testCreateFromStringIsReadableAndRewound(): void
    {
        $stream = Stream::create('hello');

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertTrue($stream->isReadable());
        $this->assertSame('hello', $stream->getContents());
    }

    public function testCreateReturnsSameStreamInstance(): void
    {
        $stream = Stream::create('x');

        $this->assertSame($stream, Stream::create($stream));
    }

    public function testConstructorRejectsNonResource(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // @mago-expect analysis:invalid-argument -- intentionally passing a non-resource to test the guard.
        new Stream('not a resource');
    }

    public function testToStringReturnsWholeBodyFromStart(): void
    {
        $stream = Stream::create('content');
        $stream->seek(3);

        $this->assertSame('content', (string) $stream);
    }

    public function testToStringReturnsEmptyStringWhenNotReadable(): void
    {
        $resource = fopen('php://temp', 'w');
        $stream = new Stream($resource);

        $this->assertSame('', (string) $stream);
    }

    public function testWriteReturnsByteCountAndAdvancesPointer(): void
    {
        $stream = Stream::create('');

        $this->assertSame(5, $stream->write('hello'));
        $this->assertSame(5, $stream->tell());
    }

    public function testReadReturnsRequestedLength(): void
    {
        $stream = Stream::create('hello world');

        $this->assertSame('hello', $stream->read(5));
    }

    public function testEofIsTrueAfterReadingPastEnd(): void
    {
        $stream = Stream::create('hi');
        $stream->getContents();
        $stream->read(1);

        $this->assertTrue($stream->eof());
    }

    public function testGetSizeReturnsByteLength(): void
    {
        $this->assertSame(5, Stream::create('hello')->getSize());
    }

    public function testSeekAndTell(): void
    {
        $stream = Stream::create('hello');
        $stream->seek(2);

        $this->assertSame(2, $stream->tell());
    }

    public function testRewindResetsPointer(): void
    {
        $stream = Stream::create('hello');
        $stream->seek(3);
        $stream->rewind();

        $this->assertSame(0, $stream->tell());
    }

    public function testDetachReturnsResourceAndDisablesStream(): void
    {
        $stream = Stream::create('hello');

        $resource = $stream->detach();

        $this->assertIsResource($resource);
        $this->assertNull($stream->getSize());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());
    }

    public function testReadOnDetachedStreamThrows(): void
    {
        $stream = Stream::create('hello');
        $stream->detach();

        $this->expectException(RuntimeException::class);

        $stream->read(1);
    }

    public function testGetMetadataReturnsArrayAndKey(): void
    {
        $stream = Stream::create('hello');

        $this->assertIsArray($stream->getMetadata());
        $this->assertSame('php://temp', $stream->getMetadata('uri'));
        $this->assertNull($stream->getMetadata('does-not-exist'));
    }

    public function testCloseReleasesResource(): void
    {
        $stream = Stream::create('hello');
        $stream->close();

        $this->assertNull($stream->detach());
    }
}
