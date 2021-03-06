<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\ResponseInterface;
use IngeniozIT\Http\Message\Message;
use IngeniozIT\Http\Message\Enums\Http;
use Psr\Http\Message\StreamInterface;
use IngeniozIT\Http\Message\Exceptions\InvalidArgumentException;

/**
 * Representation of an outgoing, server-side response.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - Status code and reason phrase
 * - Headers
 * - Message body
 *
 * Responses are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 */
class Response extends Message implements ResponseInterface
{
    const DEFAULT_STATUS_CODE = 200;
    const DEFAULT_REASON_PHRASE = Http::REASON_PHRASES[self::DEFAULT_STATUS_CODE];

    protected int $statusCode = self::DEFAULT_STATUS_CODE;
    protected string $reasonPhrase = self::DEFAULT_REASON_PHRASE;

    /**
     * Constructor.
     *
     * @param StreamInterface $stream          The StreamInterface to be used as body.
     * @param array           $headers         (optional) Headers to set.
     * @param ?string         $protocolVersion (optional) Protocol version.
     * @param int             $statusCode      (optional) HTTP status code.
     * @param string          $reasonPhrase    (optional) HTTP reason phrase.
     */
    public function __construct(
        StreamInterface $stream,
        array $headers = [],
        ?string $protocolVersion = null,
        int $statusCode = self::DEFAULT_STATUS_CODE,
        string $reasonPhrase = self::DEFAULT_REASON_PHRASE
    ) {
        $statusCode = self::formatStatusCode($statusCode);

        parent::__construct($stream);

        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
    }

    // Interface

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link   http://tools.ietf.org/html/rfc7231#section-6
     * @link   http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param  int    $code         The 3-digit integer result code to set.
     * @param  string $reasonPhrase The reason phrase to use with the
     *                              provided status code; if none is provided, implementations MAY
     *                              use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        $code = self::formatStatusCode($code);

        if ($reasonPhrase === '') {
            $reasonPhrase = Http::REASON_PHRASES[$code];
        }

        if ($this->getStatusCode() === $code && $this->getReasonPhrase() === $reasonPhrase) {
            return $this;
        }

        $response = clone $this;
        $response->statusCode = $code;
        $response->reasonPhrase = $reasonPhrase;
        return $response;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link   http://tools.ietf.org/html/rfc7231#section-6
     * @link   http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    // Internals

    /**
     * Validate and format a HTTP status code.
     *
     * @param  int $statusCode [description]
     * @return int Formatted HTTP status code.
     * @throws InvalidArgumentException If the status code is not valid.
     */
    protected static function formatStatusCode(int $statusCode): int
    {
        if (!isset(Http::REASON_PHRASES[$statusCode])) {
            throw new InvalidArgumentException("Invalid status code $statusCode.");
        }

        return $statusCode;
    }
}
