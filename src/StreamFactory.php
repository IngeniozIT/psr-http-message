<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{StreamFactoryInterface, StreamInterface};
use InvalidArgumentException;
use RuntimeException;

readonly class StreamFactory implements StreamFactoryInterface
{
    private const VALID_MODES = [
        'r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+',
        'rb', 'r+b', 'wb', 'w+b', 'ab', 'a+b', 'xb', 'x+b', 'cb', 'c+b',
        'rt', 'r+t', 'wt', 'w+t', 'at', 'a+t', 'xt', 'x+t', 'ct', 'c+t',
    ];

    public function createStream(string $content = ''): StreamInterface
    {
        $stream = $this->createStreamFromFile('php://temp', 'a+');
        $stream->write($content);
        $stream->rewind();

        return $stream;
    }

    /**
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!$this->isValidMode($mode)) {
            throw new InvalidArgumentException("Mode {$mode} is invalid");
        }

        $resource = @fopen($filename, $mode);
        if ($resource === false) {
            throw new RuntimeException("Could not open {$filename}");
        }

        return new Stream($resource);
    }

    private function isValidMode(string $mode): bool
    {
        return in_array($mode, self::VALID_MODES);
    }

    /**
     * @param resource $resource
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        $mode = stream_get_meta_data($resource)['mode'];

        if (!Stream::isReadableMode($mode)) {
            throw new InvalidArgumentException("Resource is not readable");
        }

        return new Stream($resource);
    }
}
