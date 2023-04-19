<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\{StreamFactoryInterface, StreamInterface, UploadedFileFactoryInterface, UploadedFileInterface,};

readonly class UploadedFileFactory implements UploadedFileFactoryInterface
{
    public function __construct(
        private StreamFactoryInterface $streamFactory
    ) {
    }

    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        if (!$stream->isReadable()) {
            throw new InvalidArgumentException('Stream is not readable');
        }
        return new UploadedFile(
            $stream,
            $this->streamFactory,
            $size,
            $error,
            $clientFilename,
            $clientMediaType,
        );
    }
}
