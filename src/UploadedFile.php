<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{UploadedFileInterface, StreamInterface, StreamFactoryInterface};
use RuntimeException;

class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    public function __construct(
        private readonly StreamInterface $stream,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ?int $size = null,
        private readonly int $error = UPLOAD_ERR_OK,
        private readonly ?string $clientFilename = null,
        private readonly ?string $clientMediaType = null,
    ) {
    }

    public function getStream(): StreamInterface
    {
        $this->assertNotMoved();
        return $this->stream;
    }

    public function moveTo($targetPath): void
    {
        $this->assertNotMoved();
        $this->moveStream($targetPath);
        $this->closeStream();
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    private function assertNotMoved(): void
    {
        if ($this->moved) {
            throw new RuntimeException('Stream has been moved');
        }
    }

    private function moveStream(string $targetPath): void
    {
        $newStream = $this->streamFactory->createStreamFromFile($targetPath, 'w+');
        $newStream->write((string)$this->stream);
        $this->moved = true;
    }

    private function closeStream(): void
    {
        $previousFile = $this->stream->getMetadata('uri');
        if (is_string($previousFile) && file_exists($previousFile)) {
            unlink($previousFile);
        }
        $this->stream->close();
    }
}
