<?php

declare(strict_types=1);

namespace PhpPico\Http\Message\Tests;

use InvalidArgumentException;
use PhpPico\Http\Message\Request;
use PhpPico\Http\Message\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    public function testDefaultsToGetMethodAndEmptyUri(): void
    {
        $request = new Request();

        $this->assertSame('GET', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertSame('/', $request->getRequestTarget());
    }

    public function testConstructWithMethodAndUri(): void
    {
        $request = new Request('POST', new Uri('https://example.com/path?q=1'));

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/path?q=1', $request->getRequestTarget());
    }

    public function testWithMethodImmutable(): void
    {
        $request = new Request();
        $new = $request->withMethod('DELETE');

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('DELETE', $new->getMethod());
    }

    public function testWithMethodAllowsCustomToken(): void
    {
        $request = new Request()->withMethod('PURGE');

        $this->assertSame('PURGE', $request->getMethod());
    }

    public function testWithMethodRejectsInvalidToken(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request()->withMethod('BAD METHOD');
    }

    public function testConstructorRejectsInvalidMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request('BAD METHOD');
    }

    public function testWithRequestTargetImmutable(): void
    {
        $request = new Request();
        $new = $request->withRequestTarget('*');

        $this->assertSame('/', $request->getRequestTarget());
        $this->assertSame('*', $new->getRequestTarget());
    }

    public function testWithRequestTargetRejectsWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request()->withRequestTarget('/path with space');
    }

    public function testRequestTargetFallsBackToSlashWhenPathEmpty(): void
    {
        $request = new Request('GET', new Uri('https://example.com'));

        $this->assertSame('/', $request->getRequestTarget());
    }

    public function testWithUriImmutable(): void
    {
        $request = new Request();
        $uri = new Uri('https://example.com/path');
        $new = $request->withUri($uri);

        $this->assertSame($uri, $new->getUri());
        $this->assertNotSame($uri, $request->getUri());
    }

    public function testUriHostPopulatesHostHeader(): void
    {
        $request = new Request('GET', new Uri('https://example.com/path'));

        $this->assertSame('example.com', $request->getHeaderLine('Host'));
    }

    public function testWithUriUpdatesHostHeaderByDefault(): void
    {
        $request = new Request()->withUri(new Uri('https://example.org/'));

        $this->assertSame('example.org', $request->getHeaderLine('Host'));
    }

    public function testWithUriPreserveHostKeepsExistingHostHeader(): void
    {
        $request = new Request('GET', new Uri('https://original.com/'))->withUri(
            new Uri('https://replacement.com/'),
            true,
        );

        $this->assertSame('original.com', $request->getHeaderLine('Host'));
    }

    public function testWithUriPreserveHostAdoptsUriHostWhenNoneSet(): void
    {
        $request = new Request()->withUri(new Uri('https://example.com/'), true);

        $this->assertSame('example.com', $request->getHeaderLine('Host'));
    }

    public function testHostHeaderIncludesNonStandardPort(): void
    {
        $request = new Request('GET', new Uri('https://example.com:8080/'));

        $this->assertSame('example.com:8080', $request->getHeaderLine('Host'));
    }
}
