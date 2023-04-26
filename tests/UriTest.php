<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Http\Message\Uri;
use Psr\Http\Message\UriInterface;
use IngeniozIT\Http\Message\ValueObject\{
    Scheme,
    UserInfo,
    Host,
    Port,
    Path,
    Query,
};
use InvalidArgumentException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UriTest extends TestCase
{
    protected function createUriInstance(): Uri
    {
        return new Uri(
            scheme: new Scheme(''),
            userInfo: new UserInfo('', null),
            host: new Host(''),
            port: new Port(null),
            path: new Path(''),
            query: new Query(''),
            fragment: '',
        );
    }

    public function testIsAPsrUri(): void
    {
        $uri = $this->createUriInstance();

        self::assertInstanceOf(UriInterface::class, $uri);
    }

    /**
     * @dataProvider providerSchemes
     */
    public function testHasAScheme(string $scheme, string $expectedScheme): void
    {
        $uri = $this->createUriInstance()->withScheme($scheme);

        self::assertEquals($expectedScheme, $uri->getScheme());
    }

    /**
     * @return array<string, array{scheme: string, expectedScheme: string}>
     */
    public static function providerSchemes(): array
    {
        return [
            'empty scheme' => [
                'scheme' => '',
                'expectedScheme' => '',
            ],
            'https scheme' => [
                'scheme' => 'https',
                'expectedScheme' => 'https',
            ],
            'uppercase scheme is normalized to lowercase' => [
                'scheme' => 'HTTP',
                'expectedScheme' => 'http',
            ],
        ];
    }

    /**
     * @dataProvider providerInvalidSchemes
     */
    public function testCannotHaveAnInvalidScheme(string $scheme): void
    {
        self::expectException(InvalidArgumentException::class);
        $this->createUriInstance()->withScheme($scheme);
    }

    /**
     * @return array<string, array{scheme: string}>
     */
    public static function providerInvalidSchemes(): array
    {
        return [
            'scheme starting with a number' => ['scheme' => '0http'],
            'scheme with whitespace' => ['scheme' => 'http '],
            'scheme with invalid characters' => ['scheme' => 'http:/\\@#?=&_;[]{}()!*^%$"`~|<>\''],
        ];
    }

    /**
     * @dataProvider providerUserInfo
     */
    public function testHasUserInfo(string $user, ?string $password, string $userInfo): void
    {
        $uri = $this->createUriInstance()->withUserInfo($user, $password);

        self::assertEquals($userInfo, $uri->getUserInfo());
    }

    /**
     * @return array<string, array{user: string, password: ?string, userInfo: string}>
     */
    public static function providerUserInfo(): array
    {
        return [
            'no user + no password' => ['user' => '', 'password' => null, 'userInfo' => ''],
            'user + no password' => ['user' => 'testUser', 'password' => null, 'userInfo' => 'testUser'],
            'user + password' => ['user' => 'testUser', 'password' => 'testPassword', 'userInfo' => 'testUser:testPassword'],
        ];
    }

    /**
     * @dataProvider providerHosts
     */
    public function testHasAHost(string $host, string $expectedHost): void
    {
        $uri = $this->createUriInstance()->withHost($host);

        self::assertEquals($expectedHost, $uri->getHost());
    }

    /**
     * @return array<string, array{host: string, expectedHost: string}>
     */
    public static function providerHosts(): array
    {
        return [
            'empty host' => [
                'host' => '',
                'expectedHost' => '',
            ],
            'non empty host' => [
                'host' => 'host.test',
                'expectedHost' => 'host.test',
            ],
            'uppercase host is normalized to lowercase' => [
                'host' => 'HOST.TEST',
                'expectedHost' => 'host.test',
            ],
        ];
    }

    public function testHostMustBeValid(): void
    {
        self::expectException(InvalidArgumentException::class);
        $this->createUriInstance()->withHost('!test.com');
    }

    /**
     * @dataProvider providerValidPorts
     */
    public function testCanHaveAPort(?int $port): void
    {
        $uri = $this->createUriInstance()->withPort($port);

        self::assertEquals($port, $uri->getPort());
    }

    /**
     * @return array<string, array{port: ?int}>
     */
    public static function providerValidPorts(): array
    {
        return [
            'no port' => ['port' => null],
            'min port: 1' => ['port' => 1],
            'max port: 65535' => ['port' => 65535],
        ];
    }

    /**
     * @dataProvider providerInvalidPorts
     */
    public function testPortMustNotBeOutOfRange(int $port): void
    {
        self::expectException(InvalidArgumentException::class);
        $this->createUriInstance()->withPort($port);
    }

    /**
     * @return array<string, array{port: int}>
     */
    public static function providerInvalidPorts(): array
    {
        return [
            'negative range' => ['port' => -1],
            'below range: 0' => ['port' => 0],
            'above range: 65536' => ['port' => 65536],
        ];
    }

    /**
     * @dataProvider providerSchemesAndDefaultPorts
     */
    public function testPortIsNullWhenItIsTheStandardForTheScheme(string $scheme, int $port): void
    {
        $uri = $this->createUriInstance()->withScheme($scheme)->withPort($port);

        self::assertNull($uri->getPort());
    }

    /**
     * @return array<string, array{scheme: string, port: int}>
     */
    public static function providerSchemesAndDefaultPorts(): array
    {
        return [
            'ftp' => ['scheme' => 'ftp', 'port' => 21],
            'ssh' => ['scheme' => 'ssh', 'port' => 22],
            'telnet' => ['scheme' => 'telnet', 'port' => 23],
            'smtp' => ['scheme' => 'smtp', 'port' => 25],
            'http' => ['scheme' => 'http', 'port' => 80],
            'https' => ['scheme' => 'https', 'port' => 443],
        ];
    }

    /**
     * @dataProvider providerAuthorities
     */
    public function testHasAnAuthority(
        string $user,
        ?string $password,
        ?int $port,
        string $authority,
    ): void {
        $uri = $this->createUriInstance()
            ->withUserInfo($user, $password)
            ->withPort($port)
            ->withHost('host');

        self::assertEquals($authority, $uri->getAuthority());
    }

    /**
     * @return array<string, array{user: string, password: ?string, port: ?int, authority: string}>
     */
    public static function providerAuthorities(): array
    {
        return [
            'no user + no password + no port' => [
                'user' => '',
                'password' => null,
                'port' => null,
                'authority' => 'host',
            ],
            'user + no password + no port' => [
                'user' => 'testUser',
                'password' => null,
                'port' => null,
                'authority' => 'testUser@host',
            ],
            'user + password + no port' => [
                'user' => 'testUser',
                'password' => 'testPassword',
                'port' => null,
                'authority' => 'testUser:testPassword@host',
            ],
            'no user + no password + port' => [
                'user' => '',
                'password' => null,
                'port' => 8080,
                'authority' => 'host:8080',
            ],
            'user + no password + port' => [
                'user' => 'testUser',
                'password' => null,
                'port' => 8080,
                'authority' => 'testUser@host:8080',
            ],
            'user + password + port' => [
                'user' => 'testUser',
                'password' => 'testPassword',
                'port' => 8080,
                'authority' => 'testUser:testPassword@host:8080',
            ],
        ];
    }

    /**
     * @dataProvider providerPaths
     */
    public function testHasAPath(string $inputPath, string $expectedPath): void
    {
        $uri = $this->createUriInstance()->withPath($inputPath);

        self::assertEquals($expectedPath, $uri->getPath());
    }

    /**
     * @return array<string, array{inputPath: string, expectedPath: string}>
     */
    public static function providerPaths(): array
    {
        return [
            'empty path' => [
                'inputPath' => '',
                'expectedPath' => '',
            ],
            'root path' => [
                'inputPath' => '/',
                'expectedPath' => '/',
            ],
            'absolute path' => [
                'inputPath' => '/absolute/path',
                'expectedPath' => '/absolute/path',
            ],
            'relative path' => [
                'inputPath' => 'relative/path',
                'expectedPath' => 'relative/path',
            ],
            'excess slashes at the start of the path are removed' => [
                'inputPath' => '//absolute/path',
                'expectedPath' => '/absolute/path',
            ],
            'path is percent encoded' => [
                'inputPath' => '/éà',
                'expectedPath' => '/%c3%a9%c3%a0',
            ],
            'path is not percent encoded twice' => [
                'inputPath' => '/%c3%a9%c3%a0%2f',
                'expectedPath' => '/%c3%a9%c3%a0%2f',
            ],
        ];
    }

    /**
     * @dataProvider providerQueries
     */
    public function testHasAQuery(string $inputQuery, string $expectedQuery): void
    {
        $uri = $this->createUriInstance()->withQuery($inputQuery);

        self::assertEquals($expectedQuery, $uri->getQuery());
    }

    /**
     * @return array<string, array{inputQuery: string, expectedQuery: string}>
     */
    public static function providerQueries(): array
    {
        return [
            'empty query' => [
                'inputQuery' => '',
                'expectedQuery' => '',
            ],
            'query with a param' => [
                'inputQuery' => 'foo=bar',
                'expectedQuery' => 'foo=bar',
            ],
            'query with boolean param' => [
                'inputQuery' => 'foo',
                'expectedQuery' => 'foo',
            ],
            'query with multiple params' => [
                'inputQuery' => 'foo=bar&bar=baz',
                'expectedQuery' => 'foo=bar&bar=baz',
            ],
            'query is percent encoded' => [
                'inputQuery' => 'é=à&ç=%26',
                'expectedQuery' => '%C3%A9=%C3%A0&%C3%A7=%26',
            ],
            'query is not percent encoded twice' => [
                'inputQuery' => '%C3%A9=%C3%A0&%C3%A7=%26',
                'expectedQuery' => '%C3%A9=%C3%A0&%C3%A7=%26',
            ],
        ];
    }

    /**
     * @dataProvider providerFragments
     */
    public function testHasAFragment(string $fragment): void
    {
        $uri = $this->createUriInstance()->withFragment($fragment);

        self::assertEquals($fragment, $uri->getFragment());
    }

    /**
     * @return array<string, array{fragment: string}>
     */
    public static function providerFragments(): array
    {
        return [
            'empty fragment' => ['fragment' => ''],
            'fragment' => ['fragment' => 'fragment'],
        ];
    }

    /**
     * @dataProvider providerToString
     */
    public function testCanBeConvertedToString(
        string $scheme,
        string $user,
        string $host,
        string $path,
        string $query,
        string $fragment,
        string $expectedUri,
    ): void {
        $uri = $this->createUriInstance()
            ->withScheme($scheme)
            ->withHost($host)
            ->withUserInfo($user)
            ->withPath($path)
            ->withQuery($query)
            ->withFragment($fragment);

        self::assertEquals($expectedUri, (string) $uri);
    }


    /**
     * @return array<string, array{scheme: string, user: string, host: string, path: string, query: string, fragment: string, expectedUri: string}>
     */
    public static function providerToString(): array
    {
        return [
            'path only' => [
                'scheme' => '',
                'user' => '',
                'host' => '',
                'path' => '/foo/bar',
                'query' => '',
                'fragment' => '',
                'expectedUri' => '/foo/bar',
            ],
            'scheme is suffixed by colon' => [
                'scheme' => 'http',
                'user' => '',
                'host' => '',
                'path' => '/foo/bar',
                'query' => '',
                'fragment' => '',
                'expectedUri' => 'http:/foo/bar',
            ],
            'authority is prefixed by double slash' => [
                'scheme' => 'https',
                'user' => 'user',
                'host' => 'host',
                'path' => '',
                'query' => '',
                'fragment' => '',
                'expectedUri' => 'https://user@host',
            ],
            'rootless path with authority is prefixed by slash' => [
                'scheme' => '',
                'user' => '',
                'host' => 'host',
                'path' => 'foo/bar',
                'query' => '',
                'fragment' => '',
                'expectedUri' => '//host/foo/bar',
            ],
            'path without authority cannot start with multiple slashes' => [
                'scheme' => '',
                'user' => '',
                'host' => '',
                'path' => '//foo/bar',
                'query' => '',
                'fragment' => '',
                'expectedUri' => '/foo/bar',
            ],
            'query is prefixed by question mark' => [
                'scheme' => '',
                'user' => '',
                'host' => '',
                'path' => '/foo/bar',
                'query' => 'foo=bar',
                'fragment' => '',
                'expectedUri' => '/foo/bar?foo=bar',
            ],
            'fragment is prefixed by hashtag' => [
                'scheme' => '',
                'user' => '',
                'host' => '',
                'path' => '/foo/bar',
                'query' => '',
                'fragment' => 'foobar',
                'expectedUri' => '/foo/bar#foobar',
            ],
            'query is placed before fragment' => [
                'scheme' => '',
                'user' => '',
                'host' => '',
                'path' => '/foo/bar',
                'query' => 'foo=bar',
                'fragment' => 'foobar',
                'expectedUri' => '/foo/bar?foo=bar#foobar',
            ],
        ];
    }
}
