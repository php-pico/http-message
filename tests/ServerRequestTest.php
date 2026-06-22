<?php

declare(strict_types=1);

namespace PhpPico\Http\Message\Tests;

use InvalidArgumentException;
use PhpPico\Http\Message\ServerRequest;
use PhpPico\Http\Message\Stream;
use PhpPico\Http\Message\UploadedFile;
use PhpPico\Http\Message\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerRequest::class)]
final class ServerRequestTest extends TestCase
{
    public function testInheritsRequestBehavior(): void
    {
        $request = new ServerRequest('POST', new Uri('https://example.com/path'), ['REQUEST_METHOD' => 'POST']);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/path', $request->getRequestTarget());
        $this->assertSame('example.com', $request->getHeaderLine('Host'));
        $this->assertSame(['REQUEST_METHOD' => 'POST'], $request->getServerParams());
    }

    public function testCookieParamsImmutable(): void
    {
        $request = new ServerRequest();
        $new = $request->withCookieParams(['session' => 'abc']);

        $this->assertSame([], $request->getCookieParams());
        $this->assertSame(['session' => 'abc'], $new->getCookieParams());
    }

    public function testQueryParamsImmutable(): void
    {
        $request = new ServerRequest();
        $new = $request->withQueryParams(['page' => '2']);

        $this->assertSame([], $request->getQueryParams());
        $this->assertSame(['page' => '2'], $new->getQueryParams());
    }

    public function testParsedBodyImmutable(): void
    {
        $request = new ServerRequest();
        $new = $request->withParsedBody(['field' => 'value']);

        $this->assertNull($request->getParsedBody());
        $this->assertSame(['field' => 'value'], $new->getParsedBody());
    }

    public function testUploadedFilesImmutable(): void
    {
        $file = new UploadedFile(Stream::create('x'), 1, UPLOAD_ERR_OK);
        $request = new ServerRequest();
        $new = $request->withUploadedFiles(['avatar' => $file]);

        $this->assertSame([], $request->getUploadedFiles());
        $this->assertSame(['avatar' => $file], $new->getUploadedFiles());
    }

    public function testWithUploadedFilesAcceptsNestedTree(): void
    {
        $file = new UploadedFile(Stream::create('x'), 1, UPLOAD_ERR_OK);
        $request = new ServerRequest()->withUploadedFiles(['nested' => ['deep' => $file]]);

        $this->assertSame(['nested' => ['deep' => $file]], $request->getUploadedFiles());
    }

    public function testWithUploadedFilesRejectsInvalidLeaf(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ServerRequest()->withUploadedFiles(['bad' => 'not a file']);
    }

    public function testAttributesImmutableAndAccessible(): void
    {
        $request = new ServerRequest();
        $new = $request->withAttribute('user', 'alice');

        $this->assertSame([], $request->getAttributes());
        $this->assertSame('alice', $new->getAttribute('user'));
        $this->assertSame(['user' => 'alice'], $new->getAttributes());
    }

    public function testGetAttributeReturnsDefaultWhenMissing(): void
    {
        $request = new ServerRequest();

        $this->assertNull($request->getAttribute('missing'));
        $this->assertSame('fallback', $request->getAttribute('missing', 'fallback'));
    }

    public function testWithoutAttributeRemovesIt(): void
    {
        $request = new ServerRequest()->withAttribute('user', 'alice');
        $new = $request->withoutAttribute('user');

        $this->assertSame('alice', $request->getAttribute('user'));
        $this->assertNull($new->getAttribute('user'));
    }
}
