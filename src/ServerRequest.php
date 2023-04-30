<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{ServerRequestInterface, StreamInterface, UriInterface};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Request\Method;

readonly final class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @param array<string, mixed> $serverParams
     * @param array<string, string> $cookieParams
     * @param array<string, string|bool|mixed[]> $queryParams
     * @param array<string, mixed> $uploadedFiles
     * @param null|mixed[]|object $parsedBody
     * @param array<string, mixed> $attributes
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        string $protocolVersion,
        Headers $headers,
        StreamInterface $body,
        Method $method,
        string $requestTarget,
        UriInterface $uri,
        protected array $serverParams,
        protected array $cookieParams,
        protected array $queryParams,
        protected array $uploadedFiles,
        protected null|array|object $parsedBody,
        protected array $attributes,
    ) {
        parent::__construct(
            $protocolVersion,
            $headers,
            $body,
            $method,
            $requestTarget,
            $uri,
        );
    }

    /**
     * @return array{protocolVersion: string, headers: Headers, body: StreamInterface, method: Method, requestTarget: string, uri: UriInterface, serverParams: array<string, mixed>, cookieParams: array<string, string>, queryParams: array<string, string|bool|mixed[]>, uploadedFiles: array<string, mixed>, parsedBody: null|mixed[]|object, attributes: array<string, mixed>}
     */
    protected function getConstructorParams(): array
    {
        return array_merge(
            parent::getConstructorParams(),
            [
                'serverParams' => $this->serverParams,
                'cookieParams' => $this->cookieParams,
                'queryParams' => $this->queryParams,
                'uploadedFiles' => $this->uploadedFiles,
                'parsedBody' => $this->parsedBody,
                'attributes' => $this->attributes,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @return array<string, string>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @param array<string, string> $cookies
     * @phan-suppress PhanParamTooFewUnpack
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return $cookies === $this->cookieParams ?
            $this :
            new self(...array_merge(
                $this->getConstructorParams(),
                ['cookieParams' => $cookies],
            ));
    }

    /**
     * @return array<string, string|bool|mixed[]>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param array<string, string|bool|mixed[]> $query
     * @phan-suppress PhanParamTooFewUnpack
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        return $query === $this->queryParams ?
            $this :
            new self(...array_merge(
                $this->getConstructorParams(),
                ['queryParams' => $query],
            ));
    }

    /**
     * @return array<string, mixed>
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array<string, mixed> $uploadedFiles
     * @phan-suppress PhanParamTooFewUnpack
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return $uploadedFiles === $this->uploadedFiles ?
            $this :
            new self(...array_merge(
                $this->getConstructorParams(),
                ['uploadedFiles' => $uploadedFiles],
            ));
    }

    /**
     * @return null|mixed[]|object
     */
    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody;
    }

    /**
     * @param null|mixed[]|object $data
     * @phan-suppress PhanParamTooFewUnpack
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        return $data === $this->parsedBody ?
            $this :
            new self(...array_merge(
                $this->getConstructorParams(),
                ['parsedBody' => $data],
            ));
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @phan-suppress PhanParamTooFewUnpack
     */
    public function withAttribute(string $name, mixed $value): ServerRequestInterface
    {
        return $this->getAttribute($name) === $value ?
            $this :
            new self(...array_merge(
                $this->getConstructorParams(),
                ['attributes' => array_merge($this->attributes, [$name => $value])],
            ));
    }

    /**
     * @phan-suppress PhanParamTooFewUnpack
     */
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        return !array_key_exists($name, $this->attributes) ?
            $this :
            new self(...array_merge(
                $this->getConstructorParams(),
                [
                    'attributes' => array_filter(
                        $this->attributes,
                        fn($key) => $key !== $name,
                        ARRAY_FILTER_USE_KEY
                    ),
                ],
            ));
    }
}
