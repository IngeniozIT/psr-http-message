<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UriFactoryInterface,
    UriInterface,
    ServerRequestInterface,
};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Request\Method;
use InvalidArgumentException;

readonly class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function __construct(
        private StreamFactoryInterface $streamFactory,
        private UriFactoryInterface $uriFactory,
    ) {
    }

    /**
     * @param UriInterface|string $uri
     * @param array<string, mixed> $serverParams
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest(
            protocolVersion: '',
            headers: new Headers([]),
            body: $this->streamFactory->createStream(),
            method: Method::tryFrom($method) ?? throw new InvalidArgumentException("Invalid method: $method"),
            requestTarget: '',
            uri: is_string($uri) ? $this->uriFactory->createUri($uri) : $uri,
            serverParams: $serverParams,
            cookieParams: [],
            queryParams: [],
            uploadedFiles: [],
            parsedBody: null,
            attributes: [],
        );
    }
}
