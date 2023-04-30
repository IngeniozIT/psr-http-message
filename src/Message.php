<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{MessageInterface, StreamInterface};
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
     * @return array{protocolVersion: string, headers: Headers, body: StreamInterface}
     */
    protected function getConstructorParams(): array
    {
        return [
            'protocolVersion' => $this->protocolVersion,
            'headers' => $this->headers,
            'body' => $this->body,
        ];
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @phan-suppress PhanParamTooFewUnpack
     * @phan-suppress PhanParamSignatureRealMismatchReturnType
     */
    public function withProtocolVersion(string $version): static
    {
        return $version === $this->protocolVersion ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...array_merge(
                $this->getConstructorParams(),
                ['protocolVersion' => $version],
            ));
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
     * @phan-suppress PhanParamTooFewUnpack
     * @phan-suppress PhanParamSignatureRealMismatchReturnType
     */
    public function withHeader(string $name, $value): static
    {
        return $this->headers->hasHeaderEqualTo($name, $value) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...array_merge(
                $this->getConstructorParams(),
                ['headers' => $this->headers->withHeader($name, $value)],
            ));
    }

    /**
     * @phan-suppress PhanParamTooFewUnpack
     * @phan-suppress PhanParamSignatureRealMismatchReturnType
     */
    public function withAddedHeader(string $name, $value): static
    {
        return $this->headers->hasHeaderWithValue($name, $value) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...array_merge(
                $this->getConstructorParams(),
                ['headers' => $this->headers->withHeaderValue($name, $value)],
            ));
    }

    /**
     * @phan-suppress PhanParamTooFewUnpack
     * @phan-suppress PhanParamSignatureRealMismatchReturnType
     */
    public function withoutHeader(string $name): static
    {
        return !$this->hasHeader($name) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...array_merge(
                $this->getConstructorParams(),
                ['headers' => $this->headers->withoutHeader($name)],
            ));
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @phan-suppress PhanParamTooFewUnpack
     * @phan-suppress PhanParamSignatureRealMismatchReturnType
     */
    public function withBody(StreamInterface $body): static
    {
        return $body === $this->body ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...array_merge(
                $this->getConstructorParams(),
                ['body' => $body],
            ));
    }
}
