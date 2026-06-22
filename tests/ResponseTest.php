<?php

declare(strict_types=1);

namespace PhpPico\Http\Message\Tests;

use InvalidArgumentException;
use PhpPico\Http\Message\Response;
use PhpPico\Http\Message\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testDefaultsToStatus200(): void
    {
        $response = new Response();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('1.1', $response->getProtocolVersion());
    }

    public function testWithStatusImmutableAndSetsReasonFromMap(): void
    {
        $response = new Response();
        $new = $response->withStatus(404);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(404, $new->getStatusCode());
        $this->assertSame('Not Found', $new->getReasonPhrase());
    }

    public function testWithStatusAcceptsCustomReasonPhrase(): void
    {
        $response = new Response()->withStatus(404, 'Missing');

        $this->assertSame('Missing', $response->getReasonPhrase());
    }

    public function testWithStatusRejectsOutOfRangeCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response()->withStatus(99);
    }

    public function testUnknownStatusCodeHasEmptyReasonPhrase(): void
    {
        $response = new Response()->withStatus(599);

        $this->assertSame('', $response->getReasonPhrase());
    }

    public function testWithProtocolVersionImmutable(): void
    {
        $response = new Response();
        $new = $response->withProtocolVersion('2');

        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame('2', $new->getProtocolVersion());
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $response = new Response()->withHeader('Content-Type', 'text/html');

        $this->assertTrue($response->hasHeader('content-type'));
        $this->assertSame(['text/html'], $response->getHeader('CONTENT-TYPE'));
        $this->assertSame('text/html', $response->getHeaderLine('content-type'));
    }

    public function testWithHeaderPreservesOriginalCaseInGetHeaders(): void
    {
        $response = new Response()->withHeader('Content-Type', 'text/html');

        $this->assertSame(['Content-Type' => ['text/html']], $response->getHeaders());
    }

    public function testWithHeaderImmutable(): void
    {
        $response = new Response();
        $new = $response->withHeader('X-Test', 'value');

        $this->assertFalse($response->hasHeader('X-Test'));
        $this->assertTrue($new->hasHeader('X-Test'));
    }

    public function testWithAddedHeaderAppendsValues(): void
    {
        $response = new Response()
            ->withHeader('X-Test', 'a')
            ->withAddedHeader('X-Test', 'b');

        $this->assertSame(['a', 'b'], $response->getHeader('X-Test'));
        $this->assertSame('a, b', $response->getHeaderLine('X-Test'));
    }

    public function testWithAddedHeaderCreatesHeaderWhenAbsent(): void
    {
        $response = new Response()->withAddedHeader('X-Test', 'a');

        $this->assertSame(['a'], $response->getHeader('X-Test'));
    }

    public function testWithoutHeaderRemovesCaseInsensitively(): void
    {
        $response = new Response()
            ->withHeader('X-Test', 'a')
            ->withoutHeader('x-test');

        $this->assertFalse($response->hasHeader('X-Test'));
    }

    public function testHeaderAcceptsArrayOfValues(): void
    {
        $response = new Response()->withHeader('X-Test', ['a', 'b']);

        $this->assertSame(['a', 'b'], $response->getHeader('X-Test'));
    }

    public function testMissingHeaderReturnsEmpties(): void
    {
        $response = new Response();

        $this->assertFalse($response->hasHeader('X-Missing'));
        $this->assertSame([], $response->getHeader('X-Missing'));
        $this->assertSame('', $response->getHeaderLine('X-Missing'));
    }

    public function testRejectsInvalidHeaderName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response()->withHeader('Invalid Name', 'value');
    }

    public function testRejectsHeaderValueWithLineBreak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response()->withHeader('X-Test', "line1\r\nInjected: evil");
    }

    public function testGetBodyReturnsLazyStream(): void
    {
        $response = new Response();

        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
        $this->assertSame('', (string) $response->getBody());
    }

    public function testWithBodyImmutable(): void
    {
        $response = new Response();
        $body = Stream::create('hello');
        $new = $response->withBody($body);

        $this->assertNotSame($body, $response->getBody());
        $this->assertSame($body, $new->getBody());
    }
}
