<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

readonly class Uri implements UriInterface
{
    private string $scheme;
    private string $host;
    private string $path;
    private string $query;

    private string $computedUserInfo;
    private ?int $computedPort;
    private string $computedAuthority;
    private string $computedFullUri;

    public function __construct(
        string $scheme,
        private string $user,
        private ?string $password,
        string $host,
        private ?int $port,
        string $path,
        string $query,
        private string $fragment,
    ) {
        $this->scheme = strtolower($scheme);
        $this->host = strtolower($host);
        $this->validateUri();
        $this->path = strtolower($this->urlEncodeString($path, '/'));
        $this->query = $this->urlEncodeQueryString($query);

        $this->computedUserInfo = $this->computeUserInfo();
        $this->computedPort = $this->computePort();
        $this->computedAuthority = $this->computeAuthority();
        $this->computedFullUri = $this->computeFullUri();
    }

    private function validateUri(): void
    {
        $this->assertValidPort();
        $this->assertValidHost();
        $this->assertValidScheme();
    }

    private function assertValidPort(): void
    {
        if (
            $this->port !== null &&
            filter_var(
                $this->port,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 65535]]
            ) === false
        ) {
            throw new InvalidArgumentException("Invalid port '{$this->port}'");
        }
    }

    private function assertValidHost(): void
    {
        if (!empty($this->host) && !filter_var($this->host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidArgumentException("Invalid host '{$this->host}'");
        }
    }

    private function assertValidScheme(): void
    {
        if (!empty($this->scheme) && !preg_match('/^[a-z][a-z0-9+-.]+$/', $this->scheme)) {
            throw new InvalidArgumentException("Invalid scheme '{$this->scheme}'");
        }
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

    private function computeUserInfo(): string
    {
        return $this->user . ($this->password !== null ? ':' . $this->password : '');
    }

    private function computePort(): ?int
    {
        return (getservbyname($this->scheme, 'tcp') ?: getservbyname($this->scheme, 'udp')) !== $this->port ?
            $this->port :
            null;
    }

    private function computeAuthority(): string
    {
        return (!empty($this->computedUserInfo) ? $this->computedUserInfo . '@' : '') .
            $this->host .
            ($this->computedPort ? ':' . $this->computedPort : '');
    }

    private function computeFullUri(): string
    {
        return (!empty($this->scheme) ? $this->scheme . ':' : '') .
            (!empty($this->computedAuthority) ? '//' . $this->computedAuthority : '') .
            (!empty($this->path) ? $this->cleanPath() : '') .
            ($this->query !== '' ? '?' . $this->query : '') .
            ($this->fragment !== '' ? '#' . $this->fragment : '');
    }

    private function cleanPath(): string
    {
        return (empty($this->computedAuthority) xor !str_starts_with($this->path, '/')) ?
            '/' . ltrim($this->path, '/') :
            $this->path;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        return $this->computedAuthority;
    }

    public function getUserInfo(): string
    {
        return $this->computedUserInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->computedPort;
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
            scheme: $scheme,
            user: $this->user,
            password: $this->password,
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
            user: $user,
            password: $password,
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
            user: $this->user,
            password: $this->password,
            host: $host,
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
            user: $this->user,
            password: $this->password,
            host: $this->host,
            port: $port,
            path: $this->path,
            query: $this->query,
            fragment: $this->fragment,
        );
    }

    public function withPath(string $path): self
    {
        return new self(
            scheme: $this->scheme,
            user: $this->user,
            password: $this->password,
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
            user: $this->user,
            password: $this->password,
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
            user: $this->user,
            password: $this->password,
            host: $this->host,
            port: $this->port,
            path: $this->path,
            query: $this->query,
            fragment: $fragment,
        );
    }

    public function __toString(): string
    {
        return $this->computedFullUri;
    }
}
