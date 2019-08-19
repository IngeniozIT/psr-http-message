<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Tests\Message;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\UriInterface;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\Uri
 */
class UriTest extends TestCase
{
    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /**
     * Return a generic Uri.
     *
     * @return UriInterface
     */
    protected function getUri()
    {
        return new \IngeniozIT\Http\Message\Uri();
    }

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getUri() return a UriInterface ?
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(UriInterface::class, $this->getUri());
    }

    // ========================================== //
    // Scheme                                     //
    // ========================================== //

    /**
     * If no scheme is present, this method MUST return an empty string.
     */
    public function testDefaultGetScheme()
    {
        $uri = $this->getUri();

        $this->assertSame('', $uri->getScheme());
    }

    /**
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     */
    public function testGetSchemeIsLowerCase()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withScheme('HTTP');

        $this->assertSame('http', $uri2->getScheme());
    }

    /**
     * Return an instance with the specified scheme.
     */
    public function testWithScheme()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withScheme('http');

        $this->assertSame('http', $uri2->getScheme());
    }

    /**
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     */
    public function testWithSchemeReturnsNewInstance()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withScheme('http');

        $this->assertSame('', $uri->getScheme());
        $this->assertSame('http', $uri2->getScheme());
        $this->assertNotSame($uri, $uri2);
    }

    /**
     * If the scheme given is the same as the Uri's scheme, the same instance
     * will be returned.
     */
    public function testWithSchemeReturnsSameInstanceOnSameValue()
    {
        $uri = $this->getUri()->withScheme('http');

        $uri2 = $uri->withScheme('http');

        $this->assertSame('http', $uri2->getScheme());
        $this->assertSame($uri, $uri2);
    }

    /**
     * If the scheme given is the same as the Uri's scheme, the same instance
     * will be returned.
     */
    public function testWithSchemeReturnsSameInstanceOnSameValueCaseInsensitive()
    {
        $uri = $this->getUri()->withScheme('http');

        $uri2 = $uri->withScheme('HTTP');

        $this->assertSame('http', $uri2->getScheme());
        $this->assertSame($uri, $uri2);
    }

    /**
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     */
    public function testWithSchemeImplementsHttpAndHttps()
    {
        $uri = $this->getUri()->withScheme('http');
        $this->assertSame('http', $uri->getScheme());

        $uri2 = $this->getUri()->withScheme('https');
        $this->assertSame('https', $uri2->getScheme());
    }

    /**
     * Test throws \InvalidArgumentException for invalid schemes.
     */
    public function testWithSchemeThrowsExceptionOnInvalidScheme()
    {
        $uri = $this->getUri();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withScheme([]);
    }

    /**
     * Test throws \InvalidArgumentException for unsupported schemes.
     */
    public function testWithSchemeThrowsExceptionOnUnsupportedScheme()
    {
        $uri = $this->getUri();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withScheme('notARealScheme');
    }

    /**
     * An empty scheme is equivalent to removing the scheme.
     */
    public function testWithSchemeEmptyScheme()
    {
        $uri = $this->getUri()->withScheme('http');

        $uri = $uri->withScheme('');

        $this->assertSame('', $uri->getScheme());
    }

    // ========================================== //
    // User Info                                  //
    // ========================================== //

    /**
     * If no user information is present, this method MUST return an empty
     * string.
     */
    public function testDefaultGetUserInfo()
    {
        $uri = $this->getUri();

        $this->assertSame('', $uri->getUserInfo());
    }

    /**
     * If a user is present in the URI, this will return that value.
     */
    public function testGetUriReturnsUser()
    {
        $uri = $this->getUri()->withUserInfo('username');

        $this->assertSame('username', $uri->getUserInfo());
    }

    /**
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     */
    public function testGetUriReturnsUserAndPassword()
    {
        $uri = $this->getUri()->withUserInfo('username', 'password');

        $this->assertSame('username:password', $uri->getUserInfo());
    }

    /**
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithUserInfoReturnsNewInstance()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withUserInfo('username');

        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('username', $uri2->getUserInfo());
        $this->assertNotSame($uri, $uri2);
    }

    /**
     * If the user info given is the same as the Uri's user info, the same
     * instance will be returned.
     */
    public function testWithUserInfoReturnsSameInstanceOnSameValue()
    {
        $uri = $this->getUri()->withUserInfo('username');

        $uri2 = $uri->withUserInfo('username');

        $this->assertSame('username', $uri2->getUserInfo());
        $this->assertSame($uri, $uri2);
    }

    /**
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithUserInfoWithPassReturnsNewInstance()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withUserInfo('username', 'password');

        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('username:password', $uri2->getUserInfo());
        $this->assertNotSame($uri, $uri2);
    }

    /**
     * If the user info given is the same as the Uri's user info, the same
     * instance will be returned.
     */
    public function testWithUserInfoWithPassReturnsSameInstanceOnSameValue()
    {
        $uri = $this->getUri()->withUserInfo('username', 'password');

        $uri2 = $uri->withUserInfo('username', 'password');

        $this->assertSame('username:password', $uri2->getUserInfo());
        $this->assertSame($uri, $uri2);
    }

    /**
     * An empty string for the user is equivalent to removing user
     * information.
     */
    public function testWithUserInfoEmptyString()
    {
        $uri = $this->getUri()->withUserInfo('username');
        $uri = $uri->withUserInfo('');

        $this->assertSame('', $uri->getUserInfo());
    }

    /**
     * An empty string for the user is equivalent to removing user
     * information.
     */
    public function testWithUserInfoEmptyStringWithPassword()
    {
        $uri = $this->getUri()->withUserInfo('username', 'password');
        $uri = $uri->withUserInfo('');

        $this->assertSame('', $uri->getUserInfo());
    }

    // ========================================== //
    // Host                                       //
    // ========================================== //

    /**
     * If no host is present, this method MUST return an empty string.
     */
    public function testDefaultGetHost()
    {
        $uri = $this->getUri();

        $this->assertSame('', $uri->getHost());
    }

    /**
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     */
    public function testGetHostLowercase()
    {
        $uri = $this->getUri();

        $uri = $uri->withHost('LoWeRcAsE');

        $this->assertSame('lowercase', $uri->getHost());
    }

    /**
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithHostReturnsNewInstance()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withHost('host');

        $this->assertSame('', $uri->getHost());
        $this->assertSame('host', $uri2->getHost());
        $this->assertNotSame($uri, $uri2);
    }

    /**
     * If the host given is the same as the Uri's host, the same
     * instance will be returned.
     */
    public function testWithHostReturnsSameInstanceOnSameValue()
    {
        $uri = $this->getUri()->withHost('host');

        $uri2 = $uri->withHost('host');

        $this->assertSame('host', $uri2->getHost());
        $this->assertSame($uri, $uri2);
    }

    /**
     * An empty host value is equivalent to removing the host.
     */
    public function testWithHostEmptyString()
    {
        $uri = $this->getUri()->withHost('host');
        $uri = $uri->withHost('');

        $this->assertSame('', $uri->getHost());
    }

    // ========================================== //
    // Port                                       //
    // ========================================== //

    /**
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     */
    public function testDefaultGetPort()
    {
        $uri = $this->getUri();

        $this->assertNull($uri->getPort());
    }

    /**
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     */
    public function testGetPortWithSchemeDefaultPort()
    {
        $uri = $this->getUri()->withScheme('http');

        $this->assertNull($uri->getPort());
    }

    /**
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer.
     */
    public function testGetPortWithScheme()
    {
        $uri = $this->getUri()
            ->withScheme('http')
            ->withPort(4242);

        $this->assertSame(4242, $uri->getPort());
    }

    /**
     * If the port is the standard portvused with the current scheme, this
     * method SHOULD return null.
     */
    public function testGetPortWithStandardPortForScheme()
    {
        // HTTP
        $uri = $this->getUri()
            ->withScheme('http')
            ->withPort(80);
        $this->assertNull($uri->getPort());

        // HTTPS
        $uri = $this->getUri()
            ->withScheme('https')
            ->withPort(443);
        $this->assertNull($uri->getPort());
    }

    /**
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     */
    public function testWithInvalidPortTooSmall()
    {
        $uri = $this->getUri();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPort(0);
    }

    /**
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     */
    public function testWithInvalidPortTooBig()
    {
        $uri = $this->getUri();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPort(65537);
    }

    /**
     * A null value provided for the port is equivalent to removing the port
     * information.
     */
    public function testWithPortNull()
    {
        $uri = $this->getUri()->withPort(4242);
        $uri = $uri->withPort(null);

        $this->assertNull($uri->getPort());
    }

    /**
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithPortReturnsNewInstance()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withPort(4242);

        $this->assertNull($uri->getPort());
        $this->assertSame(4242, $uri2->getPort());
        $this->assertNotSame($uri, $uri2);
    }

    /**
     * If the port given is the same as the Uri's port, the same
     * instance will be returned.
     */
    public function testWithPortReturnsSameInstanceOnSameValue()
    {
        $uri = $this->getUri()->withPort(4242);

        $uri2 = $uri->withPort(4242);

        $this->assertSame(4242, $uri2->getPort());
        $this->assertSame($uri, $uri2);
    }

    // ========================================== //
    // Authority                                  //
    // ========================================== //

    /**
     * If no authority information is present, this method MUST return an empty
     * string.
     */
    public function testDefaultGetAuthority()
    {
        $uri = $this->getUri();

        $this->assertSame('', $uri->getAuthority());
    }

    /**
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * @dataProvider getAuthoritySyntaxProvider
     */
    public function testGetAuthoritySyntax(string $host, string $user, ?string $password, ?int $port, string $expected)
    {
        $uri = $this->getUri()
            ->withHost($host)
            ->withUserInfo($user, $password)
            ->withPort($port);

        $this->assertSame($expected, $uri->getAuthority());
    }

    /**
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     */
    public function testGetAuthoritySyntaxDefaultPort()
    {
        $uri = $this->getUri()
            ->withScheme('http')
            ->withHost('hostname')
            ->withPort(80);
        $this->assertSame('hostname', $uri->getAuthority());

        $uri = $this->getUri()
            ->withScheme('https')
            ->withHost('hostname')
            ->withPort(443);
        $this->assertSame('hostname', $uri->getAuthority());
    }

    /**
     * Provider. Gives input host, user name, user password, port and the
     * expected formatted authority.
     */
    public function getAuthoritySyntaxProvider()
    {
        return [
            'Host' => [
                'hostname',
                '',
                null,
                null,
                'hostname'
            ],
            'User name' => [
                '',
                'username',
                null,
                null,
                'username@'
            ],
            'User name + User password' => [
                '',
                'username',
                'password',
                null,
                'username:password@'
            ],
            'Port' => [
                '',
                '',
                null,
                4242,
                ':4242'
            ],
            'Host + User name' => [
                'hostname',
                'username',
                null,
                null,
                'username@hostname'
            ],
            'Host + User name + User password' => [
                'hostname',
                'username',
                'password',
                null,
                'username:password@hostname'
            ],
            'Port + User name' => [
                '',
                'username',
                null,
                4242,
                'username@:4242'
            ],
            'Port + User name + User password' => [
                '',
                'username',
                'password',
                4242,
                'username:password@:4242'
            ],
            'Host + Port' => [
                'hostname',
                '',
                null,
                4242,
                'hostname:4242'
            ],
            'Host + Port + User name + User password' => [
                'hostname',
                'username',
                'password',
                4242,
                'username:password@hostname:4242'
            ],
        ];
    }

    // ========================================== //
    // Path                                       //
    // ========================================== //

    /**
     * Default path is ''.
     */
    public function testDefaultGetPath()
    {
        $uri = $this->getUri();

        $this->assertSame('', $uri->getPath());
    }

    /**
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     */
    public function testGetPathSyntax()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withPath('');
        $this->assertSame('', $uri2->getPath());

        $uri3 = $uri->withPath('/foo/bar');
        $this->assertSame('/foo/bar', $uri3->getPath());

        $uri4 = $uri->withPath('foo/bar');
        $this->assertSame('foo/bar', $uri4->getPath());
    }

    /**
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     */
    public function testGetPathEmptyVsSlash()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withPath('');
        $uri3 = $uri->withPath('/');

        $this->assertNotSame($uri2->getPath(), $uri3->getPath());
    }

    /**
     * The value returned MUST be percent-encoded.
     */
    public function testGetPathPercentEncode()
    {
        $uri = $this->getUri()->withPath('föô*BÂr+baz');

        $this->assertSame('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz', $uri->getPath());
    }

    /**
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     */
    public function testGetPathPercentEncodeDoubleEncode()
    {
        $uri = $this->getUri()->withPath('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz');

        $this->assertSame('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz', $uri->getPath());
    }

    /**
     * If a URI contains an authority component, then the path component must
     * either be empty or begin with a slash ("/") character.
     */
    public function testWithPathInvalidPathWithAuthority()
    {
        $uri = $this->getUri()->withhost('hostname');

        $uri = $uri->withPath('');
        $this->assertSame('', $uri->getPath());

        $uri = $uri->withPath('/');
        $this->assertSame('/', $uri->getPath());

        $uri = $uri->withPath('/foo/bar');
        $this->assertSame('/foo/bar', $uri->getPath());

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPath('foo/bar'); // no beginning slash
    }

    /**
     * If a URI does not contain an authority component, then the path cannot
     * begin with two slash characters ("//").
     */
    public function testWithPathInvalidNoAuthority()
    {
        $uri = $this->getUri();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPath('//foo/bar');
    }

    /**
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithPathReturnsNewInstance()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withPath('/foo/bar');

        $this->assertSame('', $uri->getPath());
        $this->assertSame('/foo/bar', $uri2->getPath());
        $this->assertNotSame($uri, $uri2);
    }

    /**
     * If the path given is the same as the Uri's path, the same
     * instance will be returned.
     */
    public function testWithPathReturnsSameInstanceOnSameValue()
    {
        $uri = $this->getUri()->withPath('/foo/bar');

        $uri2 = $uri->withPath('/foo/bar');

        $this->assertSame('/foo/bar', $uri2->getPath());
        $this->assertSame($uri, $uri2);
    }

    // ========================================== //
    // Query                                      //
    // ========================================== //

    /**
     * If no query string is present, this method MUST return an empty string.
     */
    public function testDefaultGetQuery()
    {
        $uri = $this->getUri();

        $this->assertSame('', $uri->getQuery());
    }

    /**
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     */
    public function testGetQueryLeadingQuestionMark()
    {
        $uri = $this->getUri()->withQuery('test');

        // Same as $this->assertNotSame('?test', $uri->getQuery());
        $this->assertSame('test', $uri->getQuery());
    }

    /**
     * The value returned MUST be percent-encoded.
     */
    public function testGetQueryPercentEncode()
    {
        $uri = $this->getUri()->withQuery('föô*BÂr+baz');

        $this->assertSame('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz', $uri->getQuery());
    }

    /**
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     */
    public function testGetQueryPercentEncodeDoubleEncode()
    {
        $uri = $this->getUri()->withQuery('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz');

        $this->assertSame('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz', $uri->getQuery());
    }

    /**
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithQueryReturnsNewInstance()
    {
        $uri = $this->getUri();

        $uri2 = $uri->withQuery('foo=bar&baz');

        $this->assertSame('', $uri->getQuery());
        $this->assertSame('foo=bar&baz', $uri2->getQuery());
        $this->assertNotSame($uri, $uri2);
    }

    /**
     * If the query given is the same as the Uri's query, the same
     * instance will be returned.
     */
    public function testWithQueryReturnsSameInstanceOnSameValue()
    {
        $uri = $this->getUri()->withQuery('foo=bar&baz');

        $uri2 = $uri->withQuery('foo=bar&baz');

        $this->assertSame('foo=bar&baz', $uri2->getQuery());
        $this->assertSame($uri, $uri2);
    }

    /**
     * An empty query string value is equivalent to removing the query string.
     */
    public function testWithQueryEmptyValue()
    {
        $uri = $this->getUri()->withQuery('foo=bar&baz');

        $uri = $uri->withQuery('');

        $this->assertSame('', $uri->getQuery());
    }

    // ========================================== //
    // To String                                  //
    // ========================================== //

    /**
     * Return the string representation as a URI reference.
     *
     * @dataProvider getToStringProvider
     */
    public function testToString(
        string $scheme,
        string $user,
        ?string $password,
        string $host,
        ?int $port,
        string $path,
        string $query,
        string $fragment,
        string $expectedUri
    ) {
        $uri = $this->getUri()
            ->withScheme($scheme)
            ->withUserInfo($user, $password)
            ->withPath($path)
            ->withHost($host)
            ->withPort($port)
            ->withQuery($query)
            ->withFragment($fragment);
        $this->assertSame($expectedUri, (string)$uri, "Expected {$expectedUri}, got {$uri} instead.");
    }

    /**
     * Provider. Gives input scheme, user name, user password, host, port, path,
     * query, fragment and the expected __toString output.
     */
    public function getToStringProvider()
    {
        return [
            'Full example' => [
                'http',
                'username',
                'password',
                'hostname',
                4242,
                '/path/to/foo',
                'query=foo&query2=bar',
                'fragment',
                'http://username:password@hostname:4242/path/to/foo?query=foo&query2=bar#fragment',
            ],
            'Percent encoding' => [
                'http',
                'username',
                'password',
                'hostname',
                4242,
                '/path to foo',
                'query=fo o&query2=ba r',
                'frag ment',
                'http://username:password@hostname:4242/path+to+foo?query=fo+o&query2=ba+r#frag+ment',
            ],
            'If a scheme is present, it MUST be suffixed by ":"' => [
                'http',
                '',
                null,
                '',
                null,
                '',
                '',
                '',
                'http:',
            ],
            'If an authority is present, it MUST be prefixed by "//"' => [
                '',
                '',
                null,
                'hostname',
                null,
                '',
                '',
                '',
                '//hostname',
            ],
            'If the path is rootless and an authority is present, the path MUST be prefixed by "/"' => [
                '',
                '',
                null,
                'hostname',
                null,
                'path/to/foo',
                '',
                '',
                '//hostname/path/to/foo',
            ],
            'If a query is present, it MUST be prefixed by "?"' => [
                '',
                '',
                null,
                '',
                null,
                '',
                'query=foo&query2=bar',
                '',
                '?query=foo&query2=bar',
            ],
            'If a fragment is present, it MUST be prefixed by "#"' => [
                '',
                '',
                null,
                '',
                null,
                '',
                '',
                'fragment',
                '#fragment',
            ],
        ];
    }

    /**
     * Return the string representation as a URI reference.
     *
     * @dataProvider getStringUriProvider
     * @param        string $str Expected uri.
     */
    public function testToStringFromString(string $str)
    {
        $uri = new \IngeniozIT\Http\Message\Uri($str);
        $this->assertSame($str, (string)$uri, "Expected {$str}, got {$uri} instead.");
    }

    /**
     * Provider. Gives input scheme, user name, user password, host, port, path,
     * query, fragment and the expected __toString output.
     */
    public function getStringUriProvider()
    {
        return [
            'Full example' => ['http://username:password@hostname:4242/path/to/foo?query=foo&query2=bar#fragment'],
            'If a scheme is present, it MUST be suffixed by ":"' => ['http:'],
            'If an authority is present, it MUST be prefixed by "//"' => ['//hostname'],
            'If the path is rootless and an authority is present, the path MUST be prefixed by "/"' => ['//hostname/path/to/foo'],
            'If a query is present, it MUST be prefixed by "?"' => ['?query=foo&query2=bar'],
            'If a fragment is present, it MUST be prefixed by "#"' => ['#fragment'],
        ];
    }

    /**
     * Return the string representation as a URI reference.
     *
     * @dataProvider getInvalidStringUriProvider
     * @param        string $str Invalid uri.
     */
    public function testToStringFromStringError(string $str)
    {
        $this->expectException(\InvalidArgumentException::class);
        new \IngeniozIT\Http\Message\Uri($str);
    }

    /**
     * Provider. Gives invalid uri strings.
     */
    public function getInvalidStringUriProvider()
    {
        return [
            'http:///example.com' => ['http:///example.com'],
            'http://:80' => ['http://:80'],
            'http://user@:80' => ['http://user@:80'],
        ];
    }
}
