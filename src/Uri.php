<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\UriInterface;
use IngeniozIT\Http\Message\ValueObject\{
    Scheme,
    UserInfo,
    Host,
    Port,
};

readonly class Uri implements UriInterface
{
    private string $path;
    private string $query;

    private Port $displayedPort;
    private string $authority;
    private string $fullUri;

    public function __construct(
        private Scheme $scheme,
        private UserInfo $userInfo,
        private Host $host,
        private Port $port,
        string $path,
        string $query,
        private string $fragment,
    ) {
        $this->path = strtolower($this->urlEncodeString($path, '/'));
        $this->query = $this->urlEncodeQueryString($query);

        $this->displayedPort = $this->computePort();
        $this->authority = $this->computeAuthority();
        $this->fullUri = $this->computeFullUri();
    }

    /**
     * @param non-empty-string $delimiter
     */
    private function urlEncodeString(string $path, string $delimiter): string
    {
        return implode(
            $delimiter,
            array_map(
                'rawurlencode',
                array_map(
                    'rawurldecode',
                    explode($delimiter, $path)
                )
            )
        );
    }

    private function urlEncodeQueryString(string $query): string
    {
        return implode(
            '&',
            array_map(
                fn(string $str) => $this->urlEncodeString($str, '='),
                explode('&', $query)
            )
        );
    }

    private function computePort(): Port
    {
        return $this->scheme->defaultPort() !== $this->port->value ?
            $this->port :
            new Port(null);
    }

    private function computeAuthority(): string
    {
        return $this->userInfo->toUriString() .
            $this->host .
            $this->displayedPort->toUriString();
    }

    private function computeFullUri(): string
    {
        return $this->scheme->toUriString() .
            (!empty($this->authority) ? '//' . $this->authority : '') .
            (!empty($this->path) ? $this->cleanPath() : '') .
            ($this->query !== '' ? '?' . $this->query : '') .
            ($this->fragment !== '' ? '#' . $this->fragment : '');
    }

    private function cleanPath(): string
    {
        return (empty($this->authority) xor !str_starts_with($this->path, '/')) ?
            '/' . ltrim($this->path, '/') :
            $this->path;
    }

    public function getScheme(): string
    {
        return (string) $this->scheme;
    }

    public function getAuthority(): string
    {
        return $this->authority;
    }

    public function getUserInfo(): string
    {
        return (string) $this->userInfo;
    }

    public function getHost(): string
    {
        return (string) $this->host;
    }

    public function getPort(): ?int
    {
        return $this->displayedPort->value;
    }

    public function getPath(): string
    {
        return str_starts_with($this->path, '//') ? '/' . ltrim($this->path, '/') : $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): self
    {
        return new self(
            scheme: new Scheme($scheme),
            userInfo: $this->userInfo,
            host: $this->host,
            port: $this->port,
            path: $this->path,
            query: $this->query,
            fragment: $this->fragment,
        );
    }

    public function withUserInfo(string $user, $password = null): self
    {
        return new self(
            scheme: $this->scheme,
            userInfo: new UserInfo($user, $password),
            host: $this->host,
            port: $this->port,
            path: $this->path,
            query: $this->query,
            fragment: $this->fragment,
        );
    }

    public function withHost(string $host): self
    {
        return new self(
            scheme: $this->scheme,
            userInfo: $this->userInfo,
            host: new Host($host),
            port: $this->port,
            path: $this->path,
            query: $this->query,
            fragment: $this->fragment,
        );
    }

    public function withPort(?int $port): self
    {
        return new self(
            scheme: $this->scheme,
            userInfo: $this->userInfo,
            host: $this->host,
            port: new Port($port),
            path: $this->path,
            query: $this->query,
            fragment: $this->fragment,
        );
    }

    public function withPath(string $path): self
    {
        return new self(
            scheme: $this->scheme,
            userInfo: $this->userInfo,
            host: $this->host,
            port: $this->port,
            path: $path,
            query: $this->query,
            fragment: $this->fragment,
        );
    }

    public function withQuery(string $query): self
    {
        return new self(
            scheme: $this->scheme,
            userInfo: $this->userInfo,
            host: $this->host,
            port: $this->port,
            path: $this->path,
            query: $query,
            fragment: $this->fragment,
        );
    }

    public function withFragment(string $fragment): self
    {
        return new self(
            scheme: $this->scheme,
            userInfo: $this->userInfo,
            host: $this->host,
            port: $this->port,
            path: $this->path,
            query: $this->query,
            fragment: $fragment,
        );
    }

    public function __toString(): string
    {
        return $this->fullUri;
    }
}
