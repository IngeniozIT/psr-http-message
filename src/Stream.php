<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use IngeniozIT\Http\Message\Exception\DetachedStreamException;
use IngeniozIT\Http\Message\Exception\InvalidResourceOperationException;
use IngeniozIT\Http\Message\Exception\IOException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Stream implements StreamInterface
{
    /** @var ?resource $resource */
    private $resource;
    private bool $seekable;
    private bool $writable;
    private bool $readable;
    /** @var array<string, mixed> */
    private array $metadata;

    /**
     * @param resource $resource
     */
    public function __construct(
        $resource,
    ) {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Resource is not a valid resource');
        }

        $this->resource = $resource;
        $this->metadata = stream_get_meta_data($this->resource);
        $this->seekable = $this->metadata['seekable'];
        $this->writable = self::isWritableMode($this->metadata['mode']);
        $this->readable = self::isReadableMode($this->metadata['mode']);
    }

    private static function isWritableMode(string $mode): bool
    {
        return $mode !== 'r';
    }

    public static function isReadableMode(string $mode): bool
    {
        return ($mode[1] ?? ' ') === '+' || $mode[0] === 'r';
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    public function close(): void
    {
        $resource = $this->detach();

        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    /**
     * @return ?resource
     */
    public function detach()
    {
        $resource = $this->resource;

        $this->resource = null;
        $this->seekable = false;
        $this->writable = false;
        $this->readable = false;
        $this->metadata = [];

        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }
        return fstat($this->resource)['size'] ?? null;
    }

    public function tell(): int
    {

        if ($this->resource === null) {
            throw new DetachedStreamException('Cannot tell a detached resource');
        }
        $tell = ftell($this->resource);
        if ($tell === false) {
            throw new IOException('Could not tell stream');
        }

        return $tell;
    }

    public function eof(): bool
    {
        return $this->resource === null || feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->resource === null) {
            throw new DetachedStreamException('Cannot seek a detached resource');
        }
        if (!$this->seekable) {
            throw new InvalidResourceOperationException('Resource is not seekable');
        }
        fseek($this->resource, $offset, $whence);
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write(string $string): int
    {
        if ($this->resource === null) {
            throw new DetachedStreamException('Cannot write to a detached resource');
        }
        if (!$this->writable) {
            throw new InvalidResourceOperationException('Resource is not writable');
        }
        try {
            $bytes = fwrite($this->resource, $string);
            $message = 'fwrite failed';
        } catch (Throwable $e) {
            $bytes = false;
            $message = $e->getMessage();
        }
        if ($bytes === false) {
            throw new IOException($message);
        }
        return $bytes;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        if ($length < 0) {
            throw new InvalidArgumentException('Length must be greater than 0');
        }
        return $this->readContent(fn($resource) => fread($resource, $length));
    }

    public function getContents(): string
    {
        return $this->readContent(fn($resource) => stream_get_contents($resource));
    }

    private function readContent(callable $func): string
    {
        if ($this->resource === null) {
            throw new DetachedStreamException('Cannot read from a detached resource');
        }
        if (!$this->readable) {
            throw new InvalidResourceOperationException('Resource is not readable');
        }
        try {
            $content = $func($this->resource);
            $message = 'could not read content';
        } catch (Throwable $e) {
            $content = false;
            $message = $e->getMessage();
        }
        if ($content === false) {
            throw new IOException($message);
        }
        return $content;
    }

    public function getMetadata(?string $key = null)
    {
        if ($key !== null) {
            return $this->metadata[$key] ?? null;
        }
        return $this->metadata;
    }
}
