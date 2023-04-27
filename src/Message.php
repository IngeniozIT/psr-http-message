<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use IngeniozIT\Http\Message\ValueObject\Request\Method;
use Psr\Http\Message\{MessageInterface, StreamInterface, UriInterface};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;

readonly class Message implements MessageInterface
{
    public function __construct(
        protected string $protocolVersion,
        protected Headers $headers,
        protected StreamInterface $body,
    ) {
    }

    /**
     * @param array{protocolVersion?: string, headers?: ?Headers, body?: StreamInterface, method?: Method, requestTarget?: string, uri?: UriInterface} $params
     * @return array{protocolVersion: string, headers: Headers, body: StreamInterface}
     */
    protected function newInstanceWithParams(array $params): array
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

    /**
     * @suppress PhanParamSignatureMismatch
     */
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

    /**
     * @suppress PhanParamSignatureMismatch
     */
    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->headers->hasHeaderEqualTo($name, $value) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...$this->newInstanceWithParams([
                'headers' => $this->headers->withHeader($name, $value),
            ]));
    }

    /**
     * @suppress PhanParamSignatureMismatch
     */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->headers->hasHeaderWithValue($name, $value) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...$this->newInstanceWithParams([
                'headers' => $this->headers->withHeaderValue($name, $value),
            ]));
    }

    /**
     * @suppress PhanParamSignatureMismatch
     */
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

    /**
     * @suppress PhanParamSignatureMismatch
     */
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
