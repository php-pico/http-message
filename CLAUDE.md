# php-pico/http-message

PSR-7 (`psr/http-message` v2) implementation. Zero third-party runtime dependencies.

## Commands

```bash
composer test   # phpunit
composer mago   # static analysis (mago analyze)
composer qa     # mago + phpunit (run before every commit)
```

Format with `vendor/bin/mago format`.

## Layout

- `Stream` — wraps a PHP resource; `Stream::create()` builds one from a string via `php://temp`.
- `Uri` — RFC 3986 parsing, normalization, percent-encoding.
- `MessageTrait` — headers, protocol version, lazy body. Used by `Request` and `Response`.
- `RequestTrait` — method, request target, URI, Host derivation. Used by `Request` and `ServerRequest`.
- `Request` / `ServerRequest` / `Response` / `UploadedFile` — the message classes.

## Conventions

- `declare(strict_types=1)`, `final` classes, `protected` members (not `private`).
- Self-documenting names over comments.
- Traits carry `@require-implements <Interface>` so `clone`-based `with*()` type-checks; `#[\Override]` goes on the composing class, not the trait.
- Tests use instance assertions (`$this->assertSame(...)`), not static.
- TDD: write the failing test first, then the implementation.
- `mago` is strict. Fix issues at the source; suppress only intrinsic-`mixed` PSR contracts with `// @mago-expect <category:code> -- reason`.

## Scope

PSR-7 messages only. PSR-17 factories that construct these messages live in a separate package.
