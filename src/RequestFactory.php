<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{
    RequestFactoryInterface,
    StreamFactoryInterface,
    UriFactoryInterface,
    UriInterface,
    RequestInterface,
};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Request\Method;
use InvalidArgumentException;

readonly final class RequestFactory implements RequestFactoryInterface
{
    public function __construct(
        private StreamFactoryInterface $streamFactory,
        private UriFactoryInterface $uriFactory,
    ) {
    }

    /**
     * @param UriInterface|string $uri
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request(
            protocolVersion: '',
            headers: new Headers([]),
            body: $this->streamFactory->createStream(),
            method: Method::tryFrom($method) ?? throw new InvalidArgumentException("Invalid method: $method"),
            requestTarget: '',
            uri: is_string($uri) ? $this->uriFactory->createUri($uri) : $uri,
        );
    }
}
