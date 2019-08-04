<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\UriInterface;
use IngeniozIT\Http\Message\Exceptions\InvalidArgumentException;

/**
 * Value object representing a URI.
 *
 * This interface is meant to represent URIs according to RFC 3986 and to
 * provide methods for most common operations. Additional functionality for
 * working with URIs can be provided on top of the interface or externally.
 * Its primary use is for HTTP requests, but may also be used in other
 * contexts.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * Typically the Host header will be also be present in the request message.
 * For server-side requests, the scheme will typically be discoverable in the
 * server parameters.
 *
 * @link http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
class Uri implements UriInterface
{
    /**
     * @var array[int]
     */
    protected static $ports = [
        "acap" => 674,
        "afp" => 548,
        "dict" => 2628,
        "dns" => 53,
        "file" => null,
        "ftp" => 21,
        "git" => 9418,
        "gopher" => 70,
        "http" => 80,
        "https" => 443,
        "imap" => 143,
        "ipp" => 631,
        "ipps" => 631,
        "irc" => 194,
        "ircs" => 6697,
        "ldap" => 389,
        "ldaps" => 636,
        "mms" => 1755,
        "msrp" => 2855,
        "msrps" => null,
        "mtqp" => 1038,
        "nfs" => 111,
        "nntp" => 119,
        "nntps" => 563,
        "pop" => 110,
        "prospero" => 1525,
        "redis" => 6379,
        "rsync" => 873,
        "rtsp" => 554,
        "rtsps" => 322,
        "rtspu" => 5005,
        "sftp" => 22,
        "smb" => 445,
        "snmp" => 161,
        "ssh" => 22,
        "steam" => null,
        "svn" => 3690,
        "telnet" => 23,
        "ventrilo" => 3784,
        "vnc" => 5900,
        "wais" => 210,
        "ws" => 80,
        "wss" => 443,
    ];

    /**
     * @var string
     */
    protected $scheme = '';

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var ?int
     */
    protected $port = null;

    /**
     * @var string
     */
    protected $user = '';

    /**
     * @var ?string
     */
    protected $pass = null;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $query = '';

    /**
     * @var string
     */
    protected $fragment = '';

    /**
     * Constructor.
     *
     * @param  string $uri A string version of the URI.
     * @throws InvalidArgumentException when the URI is not valid.
     */
    public function __construct(string $uri = '')
    {
        $parsed = parse_url($uri);

        if (false === $parsed) {
            throw new InvalidArgumentException('Uri could not be parsed.');
        }

        if (!empty($parsed['scheme'])) {
            $this->scheme = $parsed['scheme'];
        }
        if (!empty($parsed['host'])) {
            $this->host = $parsed['host'];
        }
        if (!empty($parsed['port'])) {
            $this->port = $parsed['port'];
        }
        if (!empty($parsed['user'])) {
            $this->user = $parsed['user'];
            $this->pass = $parsed['pass'] ?? null;
        }
        if (!empty($parsed['path'])) {
            $this->path = $parsed['path'];
        }
        if (!empty($parsed['query'])) {
            $this->query = $parsed['query'];
        }
        if (!empty($parsed['fragment'])) {
            $this->fragment = $parsed['fragment'];
        }
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see    https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see    https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        $userInfo = $this->getUserInfo();
        $port = $this->getPort();

        return $this->getUriUserInfo().$this->gethost().(null === $port ? '' : ':'.$port);
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->user.$this->getUriUserPassword();
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see    http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        $scheme = $this->getScheme();

        if (null === $this->port
            || (            '' !== $scheme
            && isset(self::$ports[$scheme])
            && self::$ports[$scheme] === $this->port)
        ) {
            return null;
        }

        return $this->port;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see    https://tools.ietf.org/html/rfc3986#section-2
     * @see    https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see    https://tools.ietf.org/html/rfc3986#section-2
     * @see    https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see    https://tools.ietf.org/html/rfc3986#section-2
     * @see    https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param  string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('Scheme must be a string.');
        }

        // Sanitize scheme
        $parsedScheme = strtolower(trim(''.$scheme));

        // Supported schemes
        if ($parsedScheme !== '' && !isset(self::$ports[$parsedScheme])) {
            throw new InvalidArgumentException("Unsupported scheme {$parsedScheme}.");
        }

        if ($this->scheme === $parsedScheme) {
            return $this;
        }

        $uri = clone $this;
        $uri->scheme = $parsedScheme;
        return $uri;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param  string      $user     The user name to use for authority.
     * @param  null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        // If the user is empty, empty the password too
        $password = ('' === $user) ? null : $password;

        if ($this->user === $user && $this->pass === $password) {
            return $this;
        }

        $uri = clone $this;
        $uri->user = $user;
        $uri->pass = $password;
        return $uri;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param  string $host The hostname to use with the new instance.
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        // Sanitize value
        $host = strtolower(trim(''.$host));

        if ($this->host === $host) {
            return $this;
        }

        $uri = clone $this;
        $uri->host = $host;
        return $uri;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param  null|int $port The port to use with the new instance; a null value
     *                        removes the port information.
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        // Validate port.
        // TCP/UDP port range goes from 1 to 65536
        if (null !== $port && (!\is_int($port) || $port < 1 || $port > 65536)) {
            throw new InvalidArgumentException('Port must be null or int between 1 and 65536');
        }

        if ($this->port === $port) {
            return $this;
        }

        $uri = clone $this;
        $uri->port = $port;
        return $uri;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param  string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        $parsedPath = static::percentEncode($path, '/');

        if ($this->getAuthority() === '') {
            // If a URI does not contain an authority component, then the path
            // cannot begin with two slash characters ("//").
            // https://tools.ietf.org/html/rfc3986#section-3.3
            if (substr($parsedPath, 0, 2) === '//') {
                throw new InvalidArgumentException('If a URI does not contain an authority component, then the path cannot begin with two slash characters ("//").');
            }
        } else {
            // If a URI contains an authority component, then the path component
            // must either be empty or begin with a slash ("/") character.
            // https://tools.ietf.org/html/rfc3986#section-3.3
            if ($parsedPath !== '' && $parsedPath[0] !== '/') {
                throw new InvalidArgumentException('If a URI contains an authority component, then the path component must either be empty or begin with a slash ("/") character.');
            }
        }

        if ($this->path === $parsedPath) {
            return $this;
        }

        $uri = clone $this;
        $uri->path = $parsedPath;
        return $uri;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param  string $query The query string to use with the new instance.
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        $parts = explode('&', $query);
        foreach ($parts as &$part) {
            $part = static::percentEncode($part, '=');
        }
        $parsedQuery = implode('&', $parts);

        if ($this->query === $parsedQuery) {
            return $this;
        }

        $uri = clone $this;
        $uri->query = $parsedQuery;
        return $uri;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param  string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        $parsedFragment = static::percentEncode($fragment);

        if ($this->fragment === $parsedFragment) {
            return $this;
        }

        $uri = clone $this;
        $uri->fragment = $parsedFragment;
        return $uri;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see    http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        return $this->getUriScheme().
            $this->getUriAuthority().
            $this->getUriPath().
            $this->getUriQuery().
            $this->getUriFragment();
    }

    /**
     * Get a URI-formatted scheme.
     *
     * @return string
     */
    protected function getUriScheme(): string
    {
        $scheme = $this->getScheme();

        // If a scheme is present, it MUST be suffixed by ":"
        return $scheme === '' ? '' : $scheme.':';
    }

    /**
     * Get a URI-formatted authority.
     *
     * @return string
     */
    protected function getUriAuthority(): string
    {
        $authority = $this->getAuthority();

        // If an authority is present, it MUST be prefixed by "//"
        return $authority === '' ? '' : '//'.$authority;
    }

    /**
     * Get a URI-formatted path.
     *
     * @return string
     */
    protected function getUriPath(): string
    {
        $path = $this->getPath();

        if ($path === '') {
            return $path;
        }

        // If the path is rootless and an authority is present, the path MUST be
        // prefixed by "/"
        if ($path[0] !== '/' && $this->getAuthority() !== '') {
            $path = '/'.$path;
        }

        return $path === '' ? '' : $path;
    }

    /**
     * Get a URI-formatted query.
     *
     * @return string
     */
    protected function getUriQuery(): string
    {
        $query = $this->getQuery();
        return $query === '' ? '' : '?'.$query;
    }

    /**
     * Get a URI-formatted fragment.
     *
     * @return string
     */
    protected function getUriFragment(): string
    {
        $fragment = $this->getFragment();
        return $fragment === '' ? '' : '#'.$fragment;
    }

    /**
     * Get a URI-formatted user password.
     *
     * @return string
     */
    protected function getUriUserPassword(): string
    {
        return ($this->pass === null ? '' : ':'.$this->pass);
    }

    /**
     * Get a URI-formatted user info.
     *
     * @return string
     */
    protected function getUriUserInfo(): string
    {
        $userInfo = $this->getUserInfo();
        return ($userInfo === '' ? '' : $userInfo.'@');
    }

    /**
     * Percent encode a string.
     *
     * @param  string  $str        The string to be encoded.
     * @param  ?string $ignoreChar A character not to encode. Null to not ignore
     *                             any character.
     * @return string The percent encoded string.
     */
    protected static function percentEncode(string $str, ?string $ignoreChar = null): string
    {
        // Empty strings
        if ($str === '' || ($ignoreChar !== null && $str === $ignoreChar)) {
            return $str;
        }

        // Detect already encoded strings
        if (preg_match('/^[a-zA-Z0-9-_~%'.($ignoreChar ? '\\'.$ignoreChar : '').']+$/', $str)) {
            return $str;
        }

        // Encode string
        if (null === $ignoreChar) {
            // No character to ignore
            $str = urlencode($str);
        } elseif ($str !== $ignoreChar) {
            // Character to ignore. Explode, encode, implode.
            $parts = explode($ignoreChar, $str);
            foreach ($parts as &$part) {
                $part = urlencode($part);
            }
            $str = implode($ignoreChar, $parts);
        }

        return $str;
    }
}
