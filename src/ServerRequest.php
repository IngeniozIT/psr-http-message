<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

readonly class ServerRequest extends Request implements ServerRequestInterface
{
    public function __construct(
        string $protocolVersion,
        array $headers,
        StreamInterface $body,
        string $method,
        string $requestTarget,
        UriInterface $uri,
        private array $serverParams,
        private array $cookieParams = [],
        private array $queryParams = [],
        private array $uploadedFiles = [],
        private null|array|object $parsedBody = null,
        private array $attributes = [],
    ) {
        parent::__construct($protocolVersion, $headers, $body, $method, $requestTarget, $uri);
    }

    protected function newInstanceParams(array $params): array
    {
        return array_merge(
            parent::newInstanceParams($params),
            [
                'serverParams' => $params['serverParams'] ?? $this->serverParams,
                'cookieParams' => $params['cookieParams'] ?? $this->cookieParams,
                'queryParams' => $params['queryParams'] ?? $this->queryParams,
                'uploadedFiles' => $params['uploadedFiles'] ?? $this->uploadedFiles,
                'parsedBody' => $params['parsedBody'] ?? $this->parsedBody,
                'attributes' => $params['attributes'] ?? $this->attributes,
            ],
        );
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): self
    {
        return $cookies === $this->cookieParams ?
            $this :
            new static(...$this->newInstanceParams([
                'cookieParams' => $cookies,
            ]));
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): self
    {
        return $query === $this->queryParams ?
            $this :
            new static(...$this->newInstanceParams([
                'queryParams' => $query,
            ]));
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        return $uploadedFiles === $this->uploadedFiles ?
            $this :
            new static(...$this->newInstanceParams([
                'uploadedFiles' => $uploadedFiles,
            ]));
    }

    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): self
    {
        if ($data !== null && !is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException('Invalid parsed body');
        }

        return $data === $this->parsedBody ?
            $this :
            new static(...$this->newInstanceParams([
                'parsedBody' => $data,
            ]));
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): self
    {
        return array_key_exists($name, $this->attributes) && $this->attributes[$name] === $value ?
            $this :
            new static(...$this->newInstanceParams([
                'attributes' => array_merge($this->attributes, [$name => $value]),
            ]));
    }

    public function withoutAttribute($name): self
    {
        return !array_key_exists($name, $this->attributes) ?
            $this :
            new static(...$this->newInstanceParams([
                'attributes' => array_filter(
                    $this->attributes,
                    fn(string $attributeName) => $attributeName !== $name,
                    ARRAY_FILTER_USE_KEY,
                ),
            ]));
    }
}
