# php-pico/http-message

A lean, dependency-free [PSR-7](https://www.php-fig.org/psr/psr-7/) implementation: immutable HTTP message objects for PHP 8.5+.

It provides the value objects defined by `psr/http-message` v2 — requests, responses, URIs, streams, and uploaded files. Every object is immutable: each `with*()` method returns a new instance and leaves the original untouched.

## Installation

```bash
composer require php-pico/http-message
```

## Usage

### Requests and responses

```php
use PhpPico\Http\Message\{Request, Response, Uri};

$request = new Request('GET', new Uri('https://example.com/users?page=2'));
$request->getMethod();          // 'GET'
$request->getRequestTarget();   // '/users?page=2'
$request->getHeaderLine('Host'); // 'example.com'

$response = new Response()
    ->withStatus(404)
    ->withHeader('Content-Type', 'application/json');

$response->getStatusCode();     // 404
$response->getReasonPhrase();   // 'Not Found'
```

### Immutability

```php
$a = new Response();
$b = $a->withStatus(500);

$a->getStatusCode(); // 200 — unchanged
$b->getStatusCode(); // 500
```

### URIs

```php
use PhpPico\Http\Message\Uri;

$uri = new Uri('https://user:pass@example.com:443/path?q=1#frag');

$uri->getScheme();    // 'https'
$uri->getHost();      // 'example.com'
$uri->getPort();      // null — 443 is the standard port for https
(string) $uri;        // 'https://user:pass@example.com/path?q=1#frag'
```

### Streams

```php
use PhpPico\Http\Message\Stream;

$body = Stream::create('Hello, world');
$response = new Response()->withBody($body);

(string) $response->getBody(); // 'Hello, world'
```

### Server requests and uploads

```php
use PhpPico\Http\Message\{ServerRequest, Uri};

$request = new ServerRequest('POST', new Uri('https://example.com/submit'), $_SERVER)
    ->withQueryParams($_GET)
    ->withParsedBody($_POST)
    ->withCookieParams($_COOKIE);

$request->getAttribute('user'); // null until set via withAttribute()
```

## Validation

Input is validated strictly per the spec: header injection (CR/LF) is rejected, header names and HTTP methods must be valid tokens, ports must be in range, and status codes must be 100–599. Invalid input throws `InvalidArgumentException`; misuse of streams or uploaded files throws `RuntimeException`.

## Creating messages from factories

This package contains the message objects only. To construct them through the [PSR-17](https://www.php-fig.org/psr/psr-17/) factory interfaces, use the companion factory package (published separately).

## License

MIT
