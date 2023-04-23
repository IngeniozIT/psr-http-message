<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

readonly class Request extends Message implements RequestInterface
{
    public function __construct(
        string $protocolVersion,
        array $headers,
        StreamInterface $body,
        protected string $method,
        protected string $requestTarget,
        protected UriInterface $uri,
    ) {
        parent::__construct($protocolVersion, $headers, $body);
    }

    protected function newInstanceParams(array $params): array
    {
        return array_merge(
            parent::newInstanceParams($params),
            [
                'method' => $params['method'] ?? $this->method,
                'requestTarget' => $params['requestTarget'] ?? $this->requestTarget,
                'uri' => $params['uri'] ?? $this->uri,
            ],
        );
    }

    public function getRequestTarget(): string
    {
        if (!empty($this->requestTarget)) {
            return $this->requestTarget;
        }
        $uriPath = $this->uri->getPath();
        if (!empty($uriPath)) {
            return str_starts_with($uriPath, '//') ? '/' . ltrim($uriPath, '/') : $uriPath;
        }
        return '/';
    }

    public function withRequestTarget($requestTarget): static
    {
        $currentRequestTarget = $this->getRequestTarget();
        return $requestTarget === $currentRequestTarget ?
            $this :
            new static(...$this->newInstanceParams([
                'requestTarget' => str_starts_with($requestTarget, '//') ? '/' . ltrim($requestTarget, '/') : $requestTarget,
            ]));
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): static
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('Invalid method');
        }

        return $method === $this->method ?
            $this :
            new static(...$this->newInstanceParams([
                'method' => $method,
            ]));
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        return $uri === $this->uri ?
            $this :
            new static(...$this->newInstanceParams([
                'uri' => $uri,
                'headers' => !empty($uri->getHost()) && (!$preserveHost || empty($this->getHeader('Host'))) ?
                    $this->addHeader('Host', [$uri->getHost()]) :
                    $this->getHeaders(),
            ]));
    }
}
