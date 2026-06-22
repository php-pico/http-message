<?php

declare(strict_types=1);

namespace PhpPico\Http\Message\Tests;

use InvalidArgumentException;
use PhpPico\Http\Message\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    public function testParsesAllComponents(): void
    {
        $uri = new Uri('https://user:pass@example.com:8080/path/to?query=1#frag');

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/path/to', $uri->getPath());
        $this->assertSame('query=1', $uri->getQuery());
        $this->assertSame('frag', $uri->getFragment());
        $this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
    }

    public function testSchemeAndHostAreLowercased(): void
    {
        $uri = new Uri('HTTPS://EXAMPLE.COM/Path');

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/Path', $uri->getPath());
    }

    #[DataProvider('defaultPortProvider')]
    public function testDefaultPortIsStripped(string $uri): void
    {
        $this->assertNull(new Uri($uri)->getPort());
    }

    public static function defaultPortProvider(): array
    {
        return [
            'http' => ['http://example.com:80/'],
            'https' => ['https://example.com:443/'],
            'ftp' => ['ftp://example.com:21/'],
            'ws' => ['ws://example.com:80/'],
            'wss' => ['wss://example.com:443/'],
            'ssh' => ['ssh://example.com:22/'],
        ];
    }

    public function testNonDefaultPortIsKept(): void
    {
        $this->assertSame(8080, new Uri('http://example.com:8080/')->getPort());
    }

    public function testDefaultPortIsOmittedFromAuthority(): void
    {
        $this->assertSame('example.com', new Uri('http://example.com:80/')->getAuthority());
    }

    public function testDefaultPortIsOmittedFromString(): void
    {
        $this->assertSame('http://example.com/', (string) new Uri('http://example.com:80/'));
    }

    public function testPortIsNormalizedLazilyWhenSchemeChanges(): void
    {
        $uri = new Uri('https://example.com:80/')->withScheme('http');

        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());
    }

    public function testDefaultPortBecomesVisibleUnderNonStandardScheme(): void
    {
        $uri = new Uri('http://example.com:80/')->withScheme('ftp');

        $this->assertSame(80, $uri->getPort());
        $this->assertSame('example.com:80', $uri->getAuthority());
    }

    public function testEmptyStringIsValidUri(): void
    {
        $uri = new Uri('');

        $this->assertSame('', (string) $uri);
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getPath());
    }

    public function testThrowsOnMalformedUri(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Uri('http://');
    }

    #[DataProvider('roundTripProvider')]
    public function testToStringRoundTrips(string $input): void
    {
        $this->assertSame($input, (string) new Uri($input));
    }

    public static function roundTripProvider(): array
    {
        return [
            ['https://example.com/'],
            ['https://user:pass@example.com:8080/path?q=1#f'],
            ['http://example.com'],
            ['/relative/path'],
            ['mailto:user@example.com'],
            ['//example.com/path'],
            ['?query-only'],
            ['#fragment-only'],
        ];
    }

    public function testWithSchemeIsImmutable(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withScheme('https');

        $this->assertNotSame($uri, $new);
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('https', $new->getScheme());
    }

    public function testWithSchemeNormalizesAndStripsDefaultPort(): void
    {
        $uri = new Uri('http://example.com:443')->withScheme('HTTPS');

        $this->assertSame('https', $uri->getScheme());
        $this->assertNull($uri->getPort());
    }

    public function testWithUserInfoImmutable(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withUserInfo('user', 'pass');

        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('user:pass', $new->getUserInfo());
    }

    public function testWithHostImmutableAndLowercased(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withHost('EXAMPLE.ORG');

        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('example.org', $new->getHost());
    }

    public function testWithPortImmutable(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPort(8080);

        $this->assertNull($uri->getPort());
        $this->assertSame(8080, $new->getPort());
    }

    #[DataProvider('invalidPortProvider')]
    public function testWithPortRejectsOutOfRange(int $port): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Uri('http://example.com')->withPort($port);
    }

    public static function invalidPortProvider(): array
    {
        return [
            'zero' => [0],
            'too high' => [65536],
            'negative' => [-1],
        ];
    }

    public function testWithPathImmutable(): void
    {
        $uri = new Uri('http://example.com/old');
        $new = $uri->withPath('/new');

        $this->assertSame('/old', $uri->getPath());
        $this->assertSame('/new', $new->getPath());
    }

    public function testWithQueryImmutable(): void
    {
        $uri = new Uri('http://example.com?a=1');
        $new = $uri->withQuery('b=2');

        $this->assertSame('a=1', $uri->getQuery());
        $this->assertSame('b=2', $new->getQuery());
    }

    public function testWithFragmentImmutable(): void
    {
        $uri = new Uri('http://example.com#old');
        $new = $uri->withFragment('new');

        $this->assertSame('old', $uri->getFragment());
        $this->assertSame('new', $new->getFragment());
    }

    public function testPercentEncodesDisallowedCharacters(): void
    {
        $uri = new Uri('')
            ->withPath('/a b')
            ->withQuery('k=a b')
            ->withFragment('a b');

        $this->assertSame('/a%20b', $uri->getPath());
        $this->assertSame('k=a%20b', $uri->getQuery());
        $this->assertSame('a%20b', $uri->getFragment());
    }

    public function testDoesNotDoubleEncodeExistingTriplets(): void
    {
        $uri = new Uri('')->withPath('/a%20b');

        $this->assertSame('/a%20b', $uri->getPath());
    }

    public function testAuthorityIsEmptyWithoutHost(): void
    {
        $this->assertSame('', new Uri('mailto:user@example.com')->getAuthority());
    }

    public function testRejectsCarriageReturnInComponents(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Uri('')->withHost("exa\r\nmple.com");
    }
}
