<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use IngeniozIT\Http\Message\Message;
use IngeniozIT\Http\Message\Uri;
use IngeniozIT\Http\Message\Stream;

/**
 * Representation of an outgoing, client-side request.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - HTTP method
 * - URI
 * - Headers
 * - Message body
 *
 * During construction, implementations MUST attempt to set the Host header from
 * a provided URI if no Host header is provided.
 *
 * Requests are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 */
class Request extends Message implements RequestInterface
{
    protected $method = 'GET';
    protected $uri;

    public function __construct(?UriInterface $uri = null, ?StreamInterface $stream = null)
    {
        // Create Stream if none given
        if ($stream === null) {
            $rs = fopen('php://temp', 'r+');
            if ($rs === false) {
                throw new Exception('Could not fopen php://temp.');
            }
            $stream = new Stream($rs);
        }

        parent::__construct($stream);

        // Create Uri if not given
        $this->uri = $uri ?? (new URI())->withPath('/');

        // During construction, implementations MUST attempt to set the Host
        // header from a provided URI if no Host header is provided.
        if ($uri !== null) {
            return $this->withHost($this->uri->getHost());
        }
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        $query = $this->uri->getQuery();
        return $this->uri->getPath().('' !== $query ? '?'.$query : '');
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link   http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param  mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        if (\is_string($requestTarget)) {
            $requestTarget = new Uri($requestTarget);
        }

        if ($requestTarget === $this->uri) {
            return $this;
        }

        $newUri = clone $this->uri;

        $newUri = $newUri
            ->withPath($requestTarget->getPath())
            ->withQuery($requestTarget->getQuery());

        return $this->withUri($requestTarget);
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param  string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $method = strtoupper($method);

        if ($method === $this->method) {
            return $this;
        }

        $request = clone $this;
        $request->method = $method;
        return $request;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link   http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link   http://tools.ietf.org/html/rfc3986#section-4.3
     * @param  UriInterface $uri          New request URI to use.
     * @param  bool         $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ((string)$uri === (string)$this->uri) {
            return $this;
        }

        $currentHost = $this->getHeaderLine('Host');
        $host = $uri->getHost();

        $request = null;
        if (false === $preserveHost
            || ('' !== $host && '' === $currentHost)
        ) {
            $request = $this->withHeader('Host', $host);
        } else {
            $request = clone $this;
        }

        $request->uri = $uri;

        return $request;
    }
}