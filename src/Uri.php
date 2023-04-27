<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use IngeniozIT\Http\Message\ValueObject\Uri\{
    Fragment,
    Host,
    Path,
    Port,
    Query,
    Scheme,
    UserInfo,
};
use Psr\Http\Message\UriInterface;

readonly class Uri implements UriInterface
{
    private Port $displayedPort;
    private string $authority;
    private string $fullUri;

    public function __construct(
        private Scheme $scheme,
        private UserInfo $userInfo,
        private Host $host,
        private Port $port,
        private Path $path,
        private Query $query,
        private Fragment $fragment,
    ) {
        $this->displayedPort = $this->computePort();
        $this->authority = $this->computeAuthority();
        $this->fullUri = $this->computeFullUri();
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
            $this->path->toUriString($this->authority) .
            $this->query->toUriString() .
            $this->fragment->toUriString();
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
        return (string) $this->path;
    }

    public function getQuery(): string
    {
        return (string) $this->query;
    }

    public function getFragment(): string
    {
        return (string) $this->fragment;
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
            path: new Path($path),
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
            query: new Query($query),
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
            fragment: new Fragment($fragment),
        );
    }

    public function __toString(): string
    {
        return $this->fullUri;
    }
}
