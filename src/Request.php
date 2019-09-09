<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\RequestInterface;
use IngeniozIT\Http\Message\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use IngeniozIT\Http\Message\Uri;
use IngeniozIT\Http\Message\Enums\Http;

use IngeniozIT\Http\Message\Exceptions\InvalidArgumentException;

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
    /**
     * @var string HTTP method.
     */
    protected $method;

    /**
     * @var UriInterface Uri.
     */
    protected $uri;

    /**
     * Constructor.
     *
     * @param StreamInterface $stream          Steam of the body of the Request.
     * @param array           $headers         (optional) HTTP headers.
     * @param ?string         $protocolVersion (optional) HTTP protocol version or null for default.
     * @param string          $method          (optional) Case-sensitive HTTP method.
     * @param ?UriInterface   $uri             (optional) Uri of the request.
     */
    public function __construct(
        StreamInterface $stream,
        array $headers = [],
        ?string $protocolVersion = null,
        string $method = 'GET',
        ?UriInterface $uri = null
    ) {
        parent::__construct($stream, $headers, $protocolVersion);

        $this->method = self::formatMethod($method);
        $this->uri = $uri ?? new Uri('/');

        // During construction, implementations MUST attempt to set the Host
        // header from a provided URI if no Host header is provided.
        if (!$this->hasHeader('Host') && !empty($host = $this->uri->getHost())) {
            $this->addHeader('Host', $host);
        }
    }

    // Interface

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
    public function getRequestTarget(): string
    {
        return (string)$this->uri;
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
    public function withRequestTarget($requestTarget): self
    {
        if (\is_string($requestTarget)) {
            $requestTarget = new Uri($requestTarget);
        }

        if ((string)$requestTarget === (string)$this->uri) {
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
    public function getMethod(): string
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
    public function withMethod($method): self
    {
        $method = self::formatMethod($method);

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
    public function getUri(): UriInterface
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
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        if ((string)$uri === (string)$this->uri) {
            return $this;
        }

        $currentHost = $this->getHeaderLine('Host');
        $nextHost = $uri->getHost();

        $request = null;
        if ($nextHost !== ''
            && $nextHost !== $currentHost
            && (            !$preserveHost
            || $currentHost === '')
        ) {
            // Host must be changed
            $request = $this->withHeader('Host', $nextHost);
        } else {
            // Host remains
            $request = clone $this;
            if ($currentHost !== $nextHost) {
                $uri = $uri->withHost($currentHost);
            }
        }

        $request->uri = $uri;

        return $request;
    }

    // Internals

    /**
     * Validate and format a HTTP method.
     *
     * @param  string $method HTTP method.
     * @return string Formatted HTTP method.
     * @throws InvalidArgumentException If the method is not valid.
     */
    protected static function formatMethod(string $method): string
    {
        if (!\in_array($method, Http::METHODS)) {
            throw new InvalidArgumentException("Invalid HTTP method \"$method\".");
        }

        return $method;
    }
}
