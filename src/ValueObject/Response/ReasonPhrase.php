<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\ValueObject\Response;

readonly final class ReasonPhrase
{
    public const REASON_PHRASES = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing', // RFC 2518, obsoleted by RFC 4918
        103 => 'Early Hints', // RFC 8297
        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information', // since HTTP/1.1
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content', // RFC 7233
        207 => 'Multi-status', // RFC 4918
        208 => 'Already Reported', // RFC 5842
        226 => 'IM Used', // RFC 3229
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // Previously "Moved temporarily"
        303 => 'See Other', // since HTTP/1.1
        304 => 'Not Modified', // RFC 7232
        305 => 'Use Proxy', // since HTTP/1.1
        306 => 'Switch Proxy', // No longer used
        307 => 'Temporary Redirect', // since HTTP/1.1
        308 => 'Permanent Redirect', // RFC 7538
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized', // RFC 7235
        402 => 'Payment Required', // RFC 7231
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed', // RFC 7231
        406 => 'Not Acceptable', // RFC 7231
        407 => 'Proxy Authentication Required', // RFC 7235
        408 => 'Request Timeout', // RFC 7231
        409 => 'Conflict',
        410 => 'Gone', // RFC 7231
        411 => 'Length Required', // RFC 7231
        412 => 'Precondition Failed', // RFC 7232
        413 => 'Payload Too Large', // RFC 7231
        414 => 'URI Too Long', // RFC 7231
        415 => 'Unsupported Media Type', // RFC 7231
        416 => 'Range Not Satisfiable', // RFC 7233
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot', // RFC 2324, RFC 7168
        421 => 'Misdirected Request', // RFC 7540
        422 => 'Unprocessable Entity', // RFC 4918
        423 => 'Locked', // RFC 4918
        424 => 'Failed Dependency', // RFC 4918
        425 => 'Too Early', // RFC 8470
        426 => 'Upgrade Required',
        428 => 'Precondition Required', // RFC 6585
        429 => 'Too Many Requests', // RFC 6585
        431 => 'Request Header Fields Too Large', // RFC 6585
        451 => 'Unavailable For Legal Reasons', // RFC 7725
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented', // RFC 7231
        502 => 'Bad Gateway', // RFC 7231
        503 => 'Service Unavailable', // RFC 7231
        504 => 'Gateway Timeout', // RFC 7231
        505 => 'HTTP Version Not Supported', // RFC 7231
        506 => 'Variant Also Negotiates', // RFC 2295
        507 => 'Insufficient Storage', // RFC 4918
        508 => 'Loop Detected', // RFC 5842
        510 => 'Not Extended', // RFC 2774
        511 => 'Network Authentication Required', // RFC 6585
    ];
}
