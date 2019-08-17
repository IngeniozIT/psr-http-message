<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\StreamInterface;
use IngeniozIT\Http\Message\Exceptions\InvalidArgumentException;
use IngeniozIT\Http\Message\Exceptions\RuntimeException;

/**
 * Describes a data stream.
 *
 * Typically, an instance will wrap a PHP stream; this interface provides
 * a wrapper around the most common operations, including serialization of
 * the entire stream to a string.
 */
class Stream implements StreamInterface
{
    /**
     * @var resource Resource wrapped by the class.
     */
    protected $resource;

    /**
     * @var bool Whether the resource is readable or not.
     */
    protected $readable;

    /**
     * @var bool Whether the resource is readable or not.
     */
    protected $writable;

    /**
     * @var bool Whether the resource is seekable or not.
     */
    protected $seekable;

    /**
     * Constructor.
     *
     * @param resource $resource The resource to be wrapped.
     */
    public function __construct($resource)
    {
        if (false === \is_resource($resource)) {
            throw new InvalidArgumentException('Stream must be a resource.');
        }

        $this->resource = $resource;

        $metadata = stream_get_meta_data($this->resource);

        if ('+' === ($metadata['mode'][1] ?? null)) {
            $this->readable = true;
            $this->writable = true;
        } else {
            $this->readable = 'r' === $metadata['mode'][0];
            $this->writable = 'w' === $metadata['mode'][0] || 'a' === $metadata['mode'][0] || 'x' === $metadata['mode'][0] || 'c' === $metadata['mode'][0];
        }

        $this->seekable = $metadata['seekable'];
    }

    /**
     * Destructor.
     *
     * Closes the stream.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see    http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        $rs = $this->detach();

        if ($rs !== null) {
            fclose($rs);
        }
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        if (!\is_resource($this->resource)) {
            return null;
        }

        $resource = $this->resource;
        $this->resource = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;

        return $resource;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        return \is_resource($this->resource) ? (fstat($this->resource)['size'] ?? null) : null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (!\is_resource($this->resource)) {
            throw new RuntimeException('Stream is detached.');
        }

        $tell = ftell($this->resource);

        if (false === $tell) {
            throw new RuntimeException('Cound not find pointer position.');
        }

        return $tell;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return !\is_resource($this->resource) || feof($this->resource);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return \is_resource($this->resource) && $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link   http://www.php.net/manual/en/function.fseek.php
     * @param  int $offset Stream offset
     * @param  int $whence Specifies how the cursor position will be calculated
     *                     based on the seek offset. Valid values are identical to the built-in
     *                     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *                     offset bytes SEEK_CUR: Set position to current location plus offset
     *                     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable() || -1 === fseek($this->resource, $offset, $whence)) {
            throw new RuntimeException('Could not seek stream (offset "'.$offset.'", whence "'.$whence.'").');
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see    seek()
     * @link   http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return \is_resource($this->resource) && $this->writable;
    }

    /**
     * Write data to the stream.
     *
     * @param  string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!\is_resource($this->resource)) {
            throw new RuntimeException('Stream is detached.');
        }

        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        $bytes = fwrite($this->resource, $string);

        if (false === $bytes) {
            throw new RuntimeException('Could not write to stream.');
        }

        return $bytes;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return \is_resource($this->resource) && $this->readable;
    }

    /**
     * Read data from the stream.
     *
     * @param  int $length Read up to $length bytes from the object and return
     *                     them. Fewer than $length bytes may be returned if underlying stream
     *                     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        if ($length === 0) {
            return '';
        }

        if ($length < 0) {
            throw new RuntimeException('Read length must be positive.');
        }

        $str = fread($this->resource, $length);

        if (false === $str) {
            throw new RuntimeException('Could not read stream.');
        }

        return $str;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        return $this->read($this->getSize());
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link   http://php.net/manual/en/function.stream-get-meta-data.php
     * @param  string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $metadata = \is_resource($this->resource) ?
            stream_get_meta_data($this->resource) :
            [];
        return null === $key ? $metadata : ($metadata[$key] ?? null);
    }
}
