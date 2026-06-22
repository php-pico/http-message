<?php

declare(strict_types=1);

namespace PhpPico\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

final class Request implements RequestInterface
{
    use RequestTrait;

    public function __construct(string $method = 'GET', ?UriInterface $uri = null)
    {
        $this->initialize($method, $uri);
    }
}
