<?php

declare(strict_types=1);

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

    /** @var string Class name of the tested class */
    protected $className = \IngeniozIT\Http\Message\Uri::class;

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getUri() return a UriInterface ?
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf(UriInterface::class, new $this->className());
    }

    // ========================================== //
    // Scheme                                     //
    // ========================================== //

    /**
     * Retrieve the scheme component of the URI.
     * If no scheme is present, this method MUST return an empty string.
     */
    public function testDefaultSchemeIsEmpty()
    {
        $uri = new $this->className();

        $this->assertSame('', $uri->getScheme());
    }

    /**
     * Return an instance with the specified scheme.
     */
    public function testCanSetScheme()
    {
        $uri = (new $this->className())->withScheme('http');

        $this->assertSame('http', $uri->getScheme());
    }

    /**
     * Return an instance with the specified scheme.
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     */
    public function testSchemeIsNormalizedToLowercase()
    {
        $uri = (new $this->className())->withScheme('HTTP');

        $this->assertSame('http', $uri->getScheme());
    }

    /**
     * Return an instance with the specified scheme.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     */
    public function testWithSchemeIsImmutable()
    {
        $uri = new $this->className();
        $uri2 = $uri->withScheme('http');

        $this->assertNotSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified scheme.
     * If the scheme given is the same as the Uri's scheme, the same instance
     * will be returned.
     */
    public function testWithSchemeReturnsSameInstanceOnSameValue()
    {
        $uri = (new $this->className())->withScheme('http');
        $uri2 = $uri->withScheme('http');

        $this->assertSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified scheme.
     * If the scheme given is the same as the Uri's scheme, the same instance
     * will be returned.
     */
    public function testWithSchemeReturnsSameInstanceOnSameValueCaseInsensitive()
    {
        $uri = (new $this->className())->withScheme('http');
        $uri2 = $uri->withScheme('HTTP');

        $this->assertSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified scheme.
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     */
    public function testWithSchemeImplementsHttp()
    {
        $uri = (new $this->className())->withScheme('http');

        $this->assertSame('http', $uri->getScheme());
    }

    /**
     * Return an instance with the specified scheme.
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     */
    public function testWithSchemeImplementsHttps()
    {
        $uri = (new $this->className())->withScheme('https');

        $this->assertSame('https', $uri->getScheme());
    }

    /**
     * Test throws \InvalidArgumentException for invalid schemes.
     */
    public function testWithSchemeThrowsExceptionOnInvalidScheme()
    {
        $uri = new $this->className();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withScheme([]);
    }

    /**
     * Return an instance with the specified scheme.
     * Test throws \InvalidArgumentException for unsupported schemes.
     */
    public function testWithSchemeThrowsInvalidArgumentExceptionOnUnsupportedScheme()
    {
        $uri = new $this->className();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withScheme('notARealScheme');
    }

    /**
     * Return an instance with the specified scheme.
     * An empty scheme is equivalent to removing the scheme.
     */
    public function testWithSchemeWithEmptyValueRemovesTheScheme()
    {
        $uri = (new $this->className())->withScheme('http');
        $uri = $uri->withScheme('');

        $this->assertSame('', $uri->getScheme());
    }

    // ========================================== //
    // User Info                                  //
    // ========================================== //

    /**
     * Retrieve the user information component of the URI.
     * If no user information is present, this method MUST return an empty
     * string.
     */
    public function testDefaultUserInfoIsEmpty()
    {
        $uri = new $this->className();

        $this->assertSame('', $uri->getUserInfo());
    }

    /**
     * Return an instance with the specified user information.
     * If a user is present in the URI, this will return that value.
     */
    public function testUserNameCanBeSet()
    {
        $uri = (new $this->className())->withUserInfo('username');

        $this->assertSame('username', $uri->getUserInfo());
    }

    /**
     * Return an instance with the specified user information.
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     */
    public function testUserPasswordCanBeSet()
    {
        $uri = (new $this->className())->withUserInfo('username', 'password');

        $this->assertSame('username:password', $uri->getUserInfo());
    }

    /**
     * Return an instance with the specified user information.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithUserInfoIsImmutable()
    {
        $uri = new $this->className();
        $uri2 = $uri->withUserInfo('username');

        $this->assertNotSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified user information.
     * If the user info given is the same as the Uri's user info, the same
     * instance will be returned.
     */
    public function testWithUserInfoReturnsSameInstanceOnSameValue()
    {
        $uri = (new $this->className())->withUserInfo('username');
        $uri2 = $uri->withUserInfo('username');

        $this->assertSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified user information.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithUserInfoWithSameUserButDifferentPasswordIsImmutable()
    {
        $uri = new $this->className();
        $uri2 = $uri->withUserInfo('username', 'password');

        $this->assertNotSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified user information.
     * If the user info given is the same as the Uri's user info, the same
     * instance will be returned.
     */
    public function testWithUserInfoWithPassReturnsSameInstanceOnSameValue()
    {
        $uri = (new $this->className())->withUserInfo('username', 'password');
        $uri2 = $uri->withUserInfo('username', 'password');

        $this->assertSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified user information.
     * An empty string for the user is equivalent to removing user
     * information.
     */
    public function testWithUserInfoWithEmptyUserRemovesUserInfo()
    {
        $uri = (new $this->className())->withUserInfo('username');
        $uri = $uri->withUserInfo('');

        $this->assertSame('', $uri->getUserInfo());
    }

    /**
     * Return an instance with the specified user information.
     * An empty string for the user is equivalent to removing user
     * information.
     */
    public function testWithUserInfoWithEmptyUserAndPasswordRemovesUserInfo()
    {
        $uri = (new $this->className())->withUserInfo('username', 'password');
        $uri = $uri->withUserInfo('');

        $this->assertSame('', $uri->getUserInfo());
    }

    // ========================================== //
    // Host                                       //
    // ========================================== //

    /**
     * Retrieve the host component of the URI.
     * If no host is present, this method MUST return an empty string.
     */
    public function testDefaultHostIsEmpty()
    {
        $uri = new $this->className();

        $this->assertSame('', $uri->getHost());
    }

    /**
     * Retrieve the host component of the URI.
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     */
    public function testGetHostNormalizesValueToLowercase()
    {
        $uri = (new $this->className())->withHost('LoWeRcAsE');

        $this->assertSame('lowercase', $uri->getHost());
    }

    /**
     * Return an instance with the specified host.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithHostIsImmutable()
    {
        $uri = new $this->className();
        $uri2 = $uri->withHost('host');

        $this->assertNotSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified host.
     * If the host given is the same as the Uri's host, the same
     * instance will be returned.
     */
    public function testWithHostReturnsSameInstanceOnSameValue()
    {
        $uri = (new $this->className())->withHost('host');
        $uri2 = $uri->withHost('host');

        $this->assertSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified host.
     * An empty host value is equivalent to removing the host.
     */
    public function testWithEmptyHostRemovesHost()
    {
        $uri = (new $this->className())->withHost('host');
        $uri = $uri->withHost('');

        $this->assertSame('', $uri->getHost());
    }

    // ========================================== //
    // Port                                       //
    // ========================================== //

    /**
     * Retrieve the port component of the URI.
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     */
    public function testDefaultPortIsNull()
    {
        $uri = new $this->className();

        $this->assertNull($uri->getPort());
    }

    /**
     * Retrieve the port component of the URI.
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     */
    public function testGetPortWithSchemeReturnsNull()
    {
        $uri = (new $this->className())->withScheme('http');

        $this->assertNull($uri->getPort());
    }

    /**
     * Return an instance with the specified port.
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer.
     */
    public function testWithNonStandardPortWithSchemeReturnsPort()
    {
        $uri = (new $this->className())
            ->withScheme('http')
            ->withPort(4242);

        $this->assertSame(4242, $uri->getPort());
    }

    /**
     * Return an instance with the specified port.
     * If the port is the standard portvused with the current scheme, this
     * method SHOULD return null.
     */
    public function testWithStandardHttpPortWithSchemeReturnsNull()
    {
        $uri = (new $this->className())
            ->withScheme('http')
            ->withPort(80);

        $this->assertNull($uri->getPort());
    }

    /**
     * Return an instance with the specified port.
     * If the port is the standard portvused with the current scheme, this
     * method SHOULD return null.
     */
    public function testWithStandardHttpsPortWithSchemeReturnsNull()
    {
        $uri = (new $this->className())
            ->withScheme('https')
            ->withPort(443);

        $this->assertNull($uri->getPort());
    }

    /**
     * Return an instance with the specified port.
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     */
    public function testWithPortTooSmallThrowsInvalidArgumentException()
    {
        $uri = new $this->className();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPort(0);
    }

    /**
     * Return an instance with the specified port.
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     */
    public function testWithPortTooBigThrowsInvalidArgumentException()
    {
        $uri = new $this->className();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPort(65537);
    }

    /**
     * Return an instance with the specified port.
     * A null value provided for the port is equivalent to removing the port
     * information.
     */
    public function testWithNullPortRemovesPort()
    {
        $uri = (new $this->className())->withPort(4242);
        $uri = $uri->withPort(null);

        $this->assertNull($uri->getPort());
    }

    /**
     * Return an instance with the specified port.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithPortIsImmutable()
    {
        $uri = new $this->className();
        $uri2 = $uri->withPort(4242);

        $this->assertNotSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified port.
     * If the port given is the same as the Uri's port, the same
     * instance will be returned.
     */
    public function testWithPortReturnsSameInstanceOnSameValue()
    {
        $uri = (new $this->className())->withPort(4242);
        $uri2 = $uri->withPort(4242);

        $this->assertSame($uri, $uri2);
    }

    // ========================================== //
    // Authority                                  //
    // ========================================== //

    /**
     * Retrieve the authority component of the URI.
     * If no authority information is present, this method MUST return an empty
     * string.
     */
    public function testDefaultAuthorityIsEmpty()
    {
        $uri = new $this->className();

        $this->assertSame('', $uri->getAuthority());
    }

    /**
     * Retrieve the authority component of the URI.
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * @dataProvider providerAuthoritySyntax
     * @param string $host Hostname.
     * @param string $user User name.
     * @param ?string $password User password.
     * @param ?int $port Port.
     * @param string $expected Expected authority.
     */
    public function testGetAuthorityHasRightSyntax(string $host, string $user, ?string $password, ?int $port, string $expected)
    {
        $uri = (new $this->className())
            ->withHost($host)
            ->withUserInfo($user, $password)
            ->withPort($port);

        $this->assertSame($expected, $uri->getAuthority());
    }

    /**
     * Provider. Gives input host, user name, user password, port and the
     * expected formatted authority.
     */
    public function providerAuthoritySyntax()
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

    /**
     * Retrieve the authority component of the URI.
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     */
    public function testGetAuthorityDoesNotIncludeDefaultPortForHttp()
    {
        $uri = (new $this->className())
            ->withScheme('http')
            ->withHost('hostname')
            ->withPort(80);

        $this->assertSame('hostname', $uri->getAuthority());
    }

    /**
     * Retrieve the authority component of the URI.
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     */
    public function testGetAuthorityDoesNotIncludeDefaultPortForHttps()
    {
        $uri = (new $this->className())
            ->withScheme('https')
            ->withHost('hostname')
            ->withPort(443);

        $this->assertSame('hostname', $uri->getAuthority());
    }

    // ========================================== //
    // Path                                       //
    // ========================================== //

    /**
     * Retrieve the path component of the URI.
     * Default path is ''.
     */
    public function testDefaultPathIsEmpty()
    {
        $uri = new $this->className();

        $this->assertSame('', $uri->getPath());
    }

    /**
     * Return an instance with the specified path.
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     */
    public function testPathCanBeEmpty()
    {
        $uri = (new $this->className())->withPath('');

        $this->assertSame('', $uri->getPath());
    }

    /**
     * Return an instance with the specified path.
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     */
    public function testPathCanBeAbsolute()
    {
        $uri = (new $this->className())->withPath('/foo/bar');

        $this->assertSame('/foo/bar', $uri->getPath());
    }

    /**
     * Return an instance with the specified path.
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     */
    public function testPathCanBeRootless()
    {
        $uri = (new $this->className())->withPath('foo/bar');

        $this->assertSame('foo/bar', $uri->getPath());
    }

    /**
     * Return an instance with the specified path.
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     */
    public function testWithPathDoesNotNormalizeEmptyAndSlash()
    {
        $uri = (new $this->className())->withPath('');
        $uri2 = $uri->withPath('/');

        $this->assertNotSame($uri->getPath(), $uri2->getPath());
    }

    /**
     * Return an instance with the specified path.
     * The value returned MUST be percent-encoded.
     */
    public function testGetPathPercentEncode()
    {
        $uri = (new $this->className())->withPath('föô*BÂr+baz');

        $this->assertSame('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz', $uri->getPath());
    }

    /**
     * Return an instance with the specified path.
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     */
    public function testGetPathPercentEncodeDoubleEncode()
    {
        $uri = (new $this->className())->withPath('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz');

        $this->assertSame('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz', $uri->getPath());
    }

    /**
     * Return an instance with the specified path.
     * If a URI contains an authority component, then the path component must
     * either be empty or begin with a slash ("/") character.
     */
    public function testWithPathWithAuthorityCanBeEmpty()
    {
        $uri = (new $this->className())->withhost('hostname');
        $uri = $uri->withPath('');

        $this->assertSame('', $uri->getPath());
    }

    /**
     * Return an instance with the specified path.
     * If a URI contains an authority component, then the path component must
     * either be empty or begin with a slash ("/") character.
     */
    public function testWithPathWithAuthorityCanBeginWithASlash()
    {
        $uri = (new $this->className())->withhost('hostname');
        $uri = $uri->withPath('/foo/bar');

        $this->assertSame('/foo/bar', $uri->getPath());
    }

    /**
     * Return an instance with the specified path.
     * If a URI contains an authority component, then the path component must
     * either be empty or begin with a slash ("/") character.
     */
    public function testWithPathWithAuthorityMustBeginWithSlashIfNotEmpty()
    {
        $uri = (new $this->className())->withhost('hostname');

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPath('foo/bar');
    }

    /**
     * Return an instance with the specified path.
     * If a URI does not contain an authority component, then the path cannot
     * begin with two slash characters ("//").
     */
    public function testWithPathWithoutAuthorityThrowsInvalidArgumentExceptionWhenPathStartsWithDoubleSlash()
    {
        $uri = new $this->className();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPath('//foo/bar');
    }

    /**
     * Return an instance with the specified path.
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithPathIsImmutable()
    {
        $uri = new $this->className();
        $uri2 = $uri->withPath('/foo/bar');

        $this->assertNotSame($uri, $uri2);
    }

    /**
     * Return an instance with the specified path.
     * If the path given is the same as the Uri's path, the same
     * instance will be returned.
     */
    public function testWithPathReturnsSameInstanceOnSameValue()
    {
        $uri = (new $this->className())->withPath('/foo/bar');
        $uri2 = $uri->withPath('/foo/bar');

        $this->assertSame($uri, $uri2);
    }

    // ========================================== //
    // Query                                      //
    // ========================================== //

    /**
     * Retrieve the query string of the URI.
     * If no query string is present, this method MUST return an empty string.
     */
    public function testGetQueryIsEmptyByDefault()
    {
        $uri = new $this->className();

        $this->assertSame('', $uri->getQuery());
    }

    /**
     * Return an instance with the specified query string.
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     */
    public function testGetQueryDoesNotHaveALeadingQuestionMark()
    {
        $uri = (new $this->className())->withQuery('test');

        $this->assertSame('test', $uri->getQuery());
    }

    /**
     * Return an instance with the specified query string.
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     */
    public function testGetQueryEncodesLeadingQuestionMark()
    {
        $uri = (new $this->className())->withQuery('?test');

        $this->assertSame('%3Ftest', $uri->getQuery());
    }

    /**
     * Return an instance with the specified query string.
     * The value returned MUST be percent-encoded.
     */
    public function testWithQueryPercentEncodesValue()
    {
        $uri = (new $this->className())->withQuery('föô*BÂr+baz');

        $this->assertSame('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz', $uri->getQuery());
    }

    /**
     * Return an instance with the specified query string.
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     */
    public function testWithQueryDoesNotPercentEncodeTwice()
    {
        $uri = (new $this->className())->withQuery('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz');

        $this->assertSame('f%C3%B6%C3%B4%2AB%C3%82r%2Bbaz', $uri->getQuery());
    }

    /**
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     */
    public function testWithQueryIsImmutable()
    {
        $uri = new $this->className();
        $uri2 = $uri->withQuery('foo=bar&baz');

        $this->assertNotSame($uri, $uri2);
    }

    /**
     * If the query given is the same as the Uri's query, the same
     * instance will be returned.
     */
    public function testWithQueryReturnsSameInstanceOnSameValue()
    {
        $uri = (new $this->className())->withQuery('foo=bar&baz');
        $uri2 = $uri->withQuery('foo=bar&baz');

        $this->assertSame($uri, $uri2);
    }

    /**
     * An empty query string value is equivalent to removing the query string.
     */
    public function testPassingEmptyQueryRemovesQuery()
    {
        $uri = (new $this->className())
            ->withQuery('foo=bar&baz')
            ->withQuery('');

        $this->assertSame('', $uri->getQuery());
    }

    // ========================================== //
    // To String                                  //
    // ========================================== //

    /**
     * Return the string representation as a URI reference.
     *
     * @dataProvider providerFullComponents
     */
    public function testReturnsValidUriStringWhenPassedComponents(
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
        $uri = (new $this->className())
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
    public function providerFullComponents()
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
     * @dataProvider providerStringUri
     * @param        string $str Expected uri.
     */
    public function testReturnsExpectedStringWhenPassingUriInConstructor(string $str)
    {
        /** @var UriInterface */
        $uri = new $this->className($str);

        $this->assertSame($str, (string)$uri, "Expected {$str}, got {$uri} instead.");
    }

    /**
     * Provider. Gives valid uri string.
     */
    public function providerStringUri()
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
     * @dataProvider providerInvalidStringUri
     * @param        string $str Invalid uri.
     */
    public function testThrowsInvalidargumentExceptionOnInvalidString(string $str)
    {
        $this->expectException(\InvalidArgumentException::class);
        new $this->className($str);
    }

    /**
     * Provider. Gives invalid uri strings.
     */
    public function providerInvalidStringUri()
    {
        return [
            'http:///example.com' => ['http:///example.com'],
            'http://:80' => ['http://:80'],
            'http://user@:80' => ['http://user@:80'],
        ];
    }
}
