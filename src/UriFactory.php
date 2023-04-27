<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use IngeniozIT\Http\Message\ValueObject\Uri\{
    Fragment,
    Host,
    Path,
    Port,
    Query,
    Scheme,
    UserInfo,
};
use InvalidArgumentException;
use Psr\Http\Message\{UriFactoryInterface, UriInterface};

readonly class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        $parsedUri = parse_url($uri);

        if ($parsedUri === false) {
            throw new InvalidArgumentException("Invalid uri $uri");
        }

        return new Uri(
            scheme: new Scheme($parsedUri['scheme'] ?? ''),
            userInfo: new UserInfo(
                user: $parsedUri['user'] ?? '',
                password: $parsedUri['pass'] ?? null,
            ),
            host: new Host($parsedUri['host'] ?? ''),
            port: new Port($parsedUri['port'] ?? null),
            path: new Path($parsedUri['path'] ?? ''),
            query: new Query($parsedUri['query'] ?? ''),
            fragment: new Fragment($parsedUri['fragment'] ?? ''),
        );
    }
}
