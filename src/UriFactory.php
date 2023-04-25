<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use IngeniozIT\Http\Message\ValueObject\{
    Scheme,
    Host,
    Port,
};
use InvalidArgumentException;

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
            user: $parsedUri['user'] ?? '',
            password: $parsedUri['pass'] ?? null,
            host: new Host($parsedUri['host'] ?? ''),
            port: !empty($parsedUri['port']) ? new Port($parsedUri['port']) : null,
            path: $parsedUri['path'] ?? '',
            query: $parsedUri['query'] ?? '',
            fragment: $parsedUri['fragment'] ?? '',
        );
    }
}
