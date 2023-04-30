<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\{RequestInterface, StreamInterface, UriInterface};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Request\Method;

readonly class Request extends Message implements RequestInterface
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
     * @return array{protocolVersion: string, headers: Headers, body: StreamInterface, method: Method, requestTarget: string, uri: UriInterface}
     */
    protected function getConstructorParams(): array
    {
        return array_merge(
            parent::getConstructorParams(),
            [
                'method' => $this->method,
                'requestTarget' => $this->requestTarget,
                'uri' => $this->uri,
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

    /**
     * @phan-suppress PhanParamTooFewUnpack
     * @phan-suppress PhanParamSignatureRealMismatchReturnType
     */
    public function withRequestTarget(string $requestTarget): static
    {
        $cleanRequestTarget = str_starts_with($requestTarget, '//') ?
            '/' . ltrim($requestTarget, '/') :
            $requestTarget;
        return $cleanRequestTarget === $this->getRequestTarget() ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...array_merge(
                $this->getConstructorParams(),
                ['requestTarget' => $cleanRequestTarget],
            ));
    }

    public function getMethod(): string
    {
        return $this->method->value;
    }



    /**
     * @phan-suppress PhanParamTooFewUnpack
     * @phan-suppress PhanParamSignatureRealMismatchReturnType
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function withMethod(string $method): static
    {
        $validMethod = Method::tryFrom($method);
        if ($validMethod === null) {
            throw new InvalidArgumentException("Invalid method: $method");
        }
        return $validMethod->value === $this->method->value ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...array_merge(
                $this->getConstructorParams(),
                ['method' => $validMethod],
            ));
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @phan-suppress PhanParamTooFewUnpack
     * @phan-suppress PhanParamSignatureRealMismatchReturnType
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        return $uri === $this->uri ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...array_merge(
                $this->getConstructorParams(),
                array_filter([
                    'uri' => $uri,
                    'headers' => $this->shouldAddHostHeader($uri, $preserveHost) ?
                        $this->headers->withHeader('Host', $uri->getHost()) :
                        null,
                ]),
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
