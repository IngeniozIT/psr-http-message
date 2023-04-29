<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\{RequestInterface, StreamInterface, UriInterface};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Request\Method;

readonly final class Request extends Message implements RequestInterface
{
    private string $cleanRequestTarget;

    public function __construct(
        string $protocolVersion,
        Headers $headers,
        StreamInterface $body,
        protected Method $method,
        protected string $requestTarget,
        protected UriInterface $uri,
    ) {
        parent::__construct($protocolVersion, $this->headersWithHost($headers, $uri), $body);
        $this->cleanRequestTarget = $this->computeRequestTarget();
    }

    private function headersWithHost(Headers $headers, UriInterface $uri): Headers
    {
        $uriHost = $uri->getHost();
        return $uriHost !== '' && !$headers->hasHeader('Host') ?
            $headers->withHeader('Host', $uriHost) :
            $headers;
    }

    /**
     * @param array{protocolVersion?: string, headers?: ?Headers, body?: StreamInterface, method?: Method, requestTarget?: string, uri?: UriInterface} $params
     * @return array{protocolVersion: string, headers: Headers, body: StreamInterface, method: Method, requestTarget: string, uri: UriInterface}
     */
    protected function newInstanceWithParams(array $params): array
    {
        return array_merge(
            parent::newInstanceWithParams($params),
            [
                'method' => $params['method'] ?? $this->method,
                'requestTarget' => $params['requestTarget'] ?? $this->requestTarget,
                'uri' => $params['uri'] ?? $this->uri,
            ],
        );
    }

    private function computeRequestTarget(): string
    {
        if (!empty($this->requestTarget)) {
            return $this->requestTarget;
        }
        $uriPath = $this->uri->getPath();
        return !empty($uriPath) ? $uriPath : '/';
    }

    public function getRequestTarget(): string
    {
        return $this->cleanRequestTarget;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $cleanRequestTarget = str_starts_with($requestTarget, '//') ?
            '/' . ltrim($requestTarget, '/') :
            $requestTarget;
        return $cleanRequestTarget === $this->getRequestTarget() ?
            $this :
            new Request(...$this->newInstanceWithParams([
                'requestTarget' => $cleanRequestTarget,
            ]));
    }

    public function getMethod(): string
    {
        return $this->method->value;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function withMethod(string $method): RequestInterface
    {
        return $method === $this->method->value ?
            $this :
            new Request(...$this->newInstanceWithParams([
                'method' => Method::tryFrom($method) ?? throw new InvalidArgumentException("Invalid method: $method"),
            ]));
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        return $uri === $this->uri ?
            $this :
            new Request(...$this->newInstanceWithParams(
                [
                    'uri' => $uri,
                    'headers' => $this->shouldAddHostHeader($uri, $preserveHost) ?
                        $this->headers->withHeader('Host', [$uri->getHost()]) :
                        null,
                ],
            ));
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function shouldAddHostHeader(UriInterface $uri, bool $preserveHost): bool
    {
        return !empty($uri->getHost()) &&
            (
                empty($this->getHeader('Host')) ||
                (!$preserveHost && $this->getHeaderLine('Host') !== $uri->getHost())
            );
    }
}
