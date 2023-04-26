<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{MessageInterface, StreamInterface};
use IngeniozIT\Http\Message\ValueObject\Headers;

readonly class Message implements MessageInterface
{
    public function __construct(
        protected string $protocolVersion,
        protected Headers $headers,
        protected StreamInterface $body,
    ) {
    }

    /**
     * @param array{protocolVersion?: string, headers?: Headers, body?: StreamInterface} $params
     * @return array{protocolVersion: string, headers: Headers, body: StreamInterface}
     */
    private function newInstanceWithParams(array $params): array
    {
        return [
            'protocolVersion' => $params['protocolVersion'] ?? $this->protocolVersion,
            'headers' => $params['headers'] ?? $this->headers,
            'body' => $params['body'] ?? $this->body,
        ];
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        return $version === $this->protocolVersion ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...$this->newInstanceWithParams([
                'protocolVersion' => $version,
            ]));
    }

    /**
     * @return array<string, string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers->toArray();
    }

    public function getHeaderLine(string $name): string
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * @return string[]
     */
    public function getHeader(string $name): array
    {
        return $this->headers->getHeaderValues($name);
    }

    public function hasHeader(string $name): bool
    {
        return $this->headers->hasHeader($name);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->headers->hasHeaderEqualTo($name, $value) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...$this->newInstanceWithParams([
                'headers' => $this->headers->withHeader($name, $value),
            ]));
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->headers->hasHeaderWithValue($name, $value) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...$this->newInstanceWithParams([
                'headers' => $this->headers->withHeaderValue($name, $value),
            ]));
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return !$this->hasHeader($name) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...$this->newInstanceWithParams([
                'headers' => $this->headers->withoutHeader($name),
            ]));
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return $body === $this->body ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...$this->newInstanceWithParams([
                'body' => $body,
            ]));
    }
}
