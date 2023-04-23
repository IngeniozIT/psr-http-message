<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract readonly class Message implements MessageInterface
{
    protected array $headersName;

    public function __construct(
        protected string $protocolVersion,
        protected array $headers,
        protected StreamInterface $body,
    ) {
        $headersName = [];
        foreach (array_keys($this->headers) as $headerName) {
            $headersName[strtolower($headerName)] = $headerName;
        }
        $this->headersName = $headersName;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): static
    {
        return $version === $this->protocolVersion ?
            $this :
            new static(...$this->newInstanceParams([
                'protocolVersion' => $version,
            ]));
    }

    /**
     * @return array{protocolVersion: string, headers: array, body: StreamInterface}
     */
    protected function newInstanceParams(array $params): array
    {
        return [
            'protocolVersion' => $params['protocolVersion'] ?? $this->protocolVersion,
            'headers' => $params['headers'] ?? $this->headers,
            'body' => $params['body'] ?? $this->body,
        ];
    }

    public function getHeaderLine($name): string
    {
        return implode(',', $this->getHeader($name));
    }

    public function getHeader($name): array
    {
        return $this->hasHeader($name) ?
            $this->headers[$this->headersName[strtolower($name)]] :
            [];
    }

    public function hasHeader($name): bool
    {
        return array_key_exists(strtolower($name), $this->headersName);
    }

    public function withHeader($name, $value): static
    {
        $headerValue = $this->sanitizeHeaderValue($name, $value);
        return array_key_exists($name, $this->headers) && $this->headers[$name] === $headerValue ?
            $this :
            new static(...$this->newInstanceParams([
                'headers' => $this->addHeader($name, $headerValue),
            ]));
    }

    protected function sanitizeHeaderValue($name, $value): array
    {
        $this->assertValidHeaderName($name);
        if (is_string($value)) {
            return [$value];
        }
        if (empty($value)) {
            throw new InvalidArgumentException('Header value must not be empty');
        }
        if (!is_array($value)) {
            throw new InvalidArgumentException('Header value must be string|array');
        }
        return array_values($value);
    }

    protected function assertValidHeaderName($name): void
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Header name must be a string');
        }
        if (empty($name)) {
            throw new InvalidArgumentException('Header name must not be empty');
        }
    }

    protected function addHeader(string $name, array $value): array
    {
        $headerValue = $this->sanitizeHeaderValue($name, $value);
        return array_merge(
            $this->removeHeader($name),
            [$name => $headerValue],
        );
    }

    protected function removeHeader(string $name): array
    {
        $this->assertValidHeaderName($name);
        return array_filter(
            $this->getHeaders(),
            fn(string $headerName) => strtolower($headerName) !== $name,
            ARRAY_FILTER_USE_KEY,
        );
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withAddedHeader($name, $value): static
    {
        $headerValue = $this->sanitizeHeaderValue($name, $value);
        return array_key_exists($name, $this->headers) && in_array($value, $this->headers[$name]) ?
            $this :
            new static(...$this->newInstanceParams([
                'headers' => $this->addHeader($name, array_merge($this->getHeader($name), $headerValue)),
            ]));
    }

    public function withoutHeader($name): static
    {
        $this->assertValidHeaderName($name);
        return $this->hasHeader($name) ?
            new static(...$this->newInstanceParams([
                'headers' => $this->removeHeader($name),
            ])) :
            $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        return $body === $this->body ?
            $this :
            new static(...$this->newInstanceParams([
                'body' => $body,
            ]));
    }
}
