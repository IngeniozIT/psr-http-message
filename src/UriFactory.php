<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

readonly class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        $parsedUri = parse_url($uri);

        if ($parsedUri === false) {
            throw new InvalidArgumentException("Invalid uri $uri");
        }

        return new Uri(
            scheme: $parsedUri['scheme'] ?? '',
            user: $parsedUri['user'] ?? '',
            password: $parsedUri['pass'] ?? null,
            host: $parsedUri['host'] ?? '',
            port: $parsedUri['port'] ?? null,
            path: $parsedUri['path'] ?? '',
            query: $parsedUri['query'] ?? '',
            fragment: $parsedUri['fragment'] ?? '',
        );
    }
}
