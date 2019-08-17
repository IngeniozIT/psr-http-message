<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\MessageInterface;

use Psr\Http\Message\StreamInterface;

use IngeniozIT\Http\Message\Exceptions\FileSystemException;
use IngeniozIT\Http\Message\Exceptions\InvalidArgumentException;

/**
 * HTTP messages consist of requests from a client to a server and responses
 * from a server to a client. This interface defines the methods common to
 * each.
 *
 * Messages are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 *
 * @link http://www.ietf.org/rfc/rfc7230.txt
 * @link http://www.ietf.org/rfc/rfc7231.txt
 */
class Message implements MessageInterface
{
    /**
     * @var string Default HTTP protocol version.
     */
    const DEFAULT_PROTOCOL_VERSION = '1.1';

    /**
     * @var string HTTP protocol version.
     */
    protected $protocolVersion;

    /**
     * @var array HTTP headers.
     */
    protected $headers = [];

    /**
     * @var array Mapping between sanitized header names and given header names.
     */
    protected $headerNames = [];

    /**
     * @var StreamInterface Body of the message.
     */
    protected $body = null;

    /**
     * Constructor.
     *
     * @param StreamInterface $stream The StreamInterface to be used as body.
     * @param array (optional) $headers Headers to set.
     * @param ?string (optional) $protocolVersion Protocol version.
     */
    public function __construct(StreamInterface $stream, array $headers = [], ?string $protocolVersion = null)
    {
        // Add protocol version
        if ($protocolVersion !== null) {
            $this->protocolVersion = self::formatProtocolVersion($protocolVersion);
        } else {
            $this->protocolVersion = static::DEFAULT_PROTOCOL_VERSION;
        }

        // Add headers
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }

        $this->body = $stream;
    }

    // Interface

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param  string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        $version = self::formatProtocolVersion($version);

        if ($this->protocolVersion === $version) {
            return $this;
        }

        $message = clone $this;
        $message->protocolVersion = $version;
        return $message;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param  string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        return isset($this->headerNames[static::parseHeaderName($name)]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param  string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name)
    {
        $parsedHeaderName = static::parseHeaderName($name);
        return isset($this->headerNames[$parsedHeaderName]) ?
            $this->headers[$this->headerNames[$parsedHeaderName]] :
            [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param  string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        $parsedHeaderName = static::parseHeaderName($name);
        return isset($this->headerNames[$parsedHeaderName]) ?
            implode(',', $this->headers[$this->headerNames[$parsedHeaderName]]) :
            '';
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param  string          $name  Case-insensitive header field name.
     * @param  string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        $parsedHeaderName = static::parseHeaderName($name);
        $parsedHeaderValue = static::parseHeaderValue($value);

        if (isset($this->headerNames[$parsedHeaderName])
            && $this->headerNames[$parsedHeaderName] === $name
            && $this->headers[$this->headerNames[$parsedHeaderName]] === $parsedHeaderValue
        ) {
            return $this;
        }

        $message = clone $this;
        if (isset($message->headerNames[$parsedHeaderName])) {
            unset($message->headers[$message->headerNames[$parsedHeaderName]]);
        }
        $message->headerNames[$parsedHeaderName] = $name;
        $message->headers[$name] = $parsedHeaderValue;

        return $message;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param  string          $name  Case-insensitive header field name to add.
     * @param  string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        $parsedHeaderName = static::parseHeaderName($name);
        $parsedHeaderValue = static::parseHeaderValue($value);
        $previousHeaderValue = $this->getHeader($name);

        $message = clone $this;
        return $message->withHeader($name, array_merge($previousHeaderValue, $parsedHeaderValue));
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param  string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name)
    {
        $parsedHeaderName = static::parseHeaderName($name);

        if (!isset($this->headerNames[$parsedHeaderName])) {
            return $this;
        }

        $message = clone $this;
        unset($message->headers[$message->headerNames[$parsedHeaderName]]);
        unset($message->headerNames[$parsedHeaderName]);
        return $message;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param  StreamInterface $body Body.
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        if ($this->body === $body) {
            return $this;
        }

        $message = clone $this;
        $message->body = $body;
        return $message;
    }

    // Internals

    /**
     * Add a header to the Message.
     *
     * @param  string          $name  Case-insensitive header field name to add.
     * @param  string|string[] $value Header value(s).
     * @return void
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    protected function addHeader($name, $value): void
    {
        $parsedHeaderName = static::parseHeaderName($name);
        $parsedHeaderValue = static::parseHeaderValue($value);

        if (isset($this->headerNames[$parsedHeaderName])) {
            unset($this->headers[$this->headerNames[$parsedHeaderName]]);
        }

        $this->headerNames[$parsedHeaderName] = $name;
        $this->headers[$name] = $parsedHeaderValue;
    }

    /**
     * Parse and sanitize a header name.
     *
     * @param  string $name The header name.
     * @return string The sanitized header name.
     * @throws InvalidArgumentException If $name cannot be converted to string.
     */
    protected static function parseHeaderName($name): string
    {
        if (!self::isToStringable($name)) {
            throw new InvalidArgumentException("The header name must be a string.");
        }

        return trim(strtolower((string)$name));
    }

    /**
     * Parse and sanitize a header value.
     *
     * @param  string|string[] $value The header value.
     * @return array The sanitized header value.
     * @throws InvalidArgumentException If $value cannot be converted to a
     * string[].
     */
    protected static function parseHeaderValue($value): array
    {
        if (!\is_array($value)) {
            $value = [$value];
        }

        foreach ($value as &$val) {
            if (!self::isToStringable($val)) {
                throw new InvalidArgumentException("The header values must be strings.");
            }
            $val = trim((string)$val);
        }

        return $value;
    }

    /**
     * Check if a value can be converted to string.
     *
     * @param  mixed $value The value.
     * @return bool True if the value can be converted, false otherwise.
     */
    protected static function isToStringable($value): bool
    {
        return (
            $value === null ||
            is_scalar($value) ||
            (is_object($value) && method_exists($value, '__toString'))
        );
    }

    /**
     * Format a protocol version.
     *
     * @param  mixed $version Protocol version.
     * @return string The formatted protocol version.
     * @throws InvalidArgumentException If $version is an invalid protocol version.
     */
    protected static function formatProtocolVersion($version): string
    {
        // Check if $version can be converted to string.
        if (!self::isToStringable($version)) {
           throw new InvalidArgumentException("The version must be a string.");
        }

        $version = (string)$version;

        // Check if $version has the right format
        if (preg_match('/^\d+(\.\d+)?$/', $version) !== 1) {
           throw new InvalidArgumentException("The version string MUST contain only the HTTP version number (e.g., \"1.1\", \"1.0\"), {$version} given.");
        }

        return $version;
    }
}
