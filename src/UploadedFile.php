<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{UploadedFileInterface, StreamInterface};
use IngeniozIT\Http\Message\Enums\File;
use IngeniozIT\Http\Message\Exceptions\{InvalidArgumentException, RuntimeException, FileSystemException};

/**
 * Value object representing a file uploaded through an HTTP request.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 */
class UploadedFile implements UploadedFileInterface
{
    protected $stream;
    protected ?int $size;
    protected int $error;
    protected ?string $clientFilename;
    protected ?string $clientMediaType;

    public function __construct(
        StreamInterface $stream,
        ?int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ) {
        $this->validateError($error);
        $this->error = $error;

        $this->stream = $stream;
        $this->size = $size ?? $stream->getSize();
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * Validate the error associated with the uploaded file.
     *
     * @throws InvalidArgumentException When $error is not valid.
     */
    protected static function validateError(int $error): void
    {
        if (!isset(File::ERROR_STATUS[$error])) {
            throw new InvalidArgumentException('Error status must be one of PHP\'s UPLOAD_ERR_XXX constants.');
        }
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream(): StreamInterface
    {
        if (!($this->stream instanceof StreamInterface)) {
            throw new RuntimeException('Stream has been moved.');
        }

        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method is
     * guaranteed to work in both SAPI and non-SAPI environments.
     * Implementations must determine which environment they are in, and use the
     * appropriate method (move_uploaded_file(), rename(), or a stream
     * operation) to perform the operation.
     *
     * $targetPath may be an absolute path, or a relative path. If it is a
     * relative path, resolution should be the same as used by PHP's rename()
     * function.
     *
     * The original file or stream MUST be removed on completion.
     *
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     *
     * When used in an SAPI environment where $_FILES is populated, when writing
     * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
     * used to ensure permissions and upload status are verified correctly.
     *
     * If you wish to move to a stream, use getStream(), as SAPI operations
     * cannot guarantee writing to stream destinations.
     *
     * @see    http://php.net/is_uploaded_file
     * @see    http://php.net/move_uploaded_file
     * @param  string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $targetPath specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath): void
    {
        if (!($this->stream instanceof StreamInterface)) {
            throw new RuntimeException('Stream has been moved.');
        }

        $this->validateTargetPath($targetPath);
        $this->copyStreamToPath($targetPath);
        $this->stream->close();
        $this->stream = null;
    }

    /**
     * Check if a file can be created at a target path.
     *
     * @param string $targetPath
     */
    protected function validateTargetPath(string $targetPath): void
    {
        $fullTargetPath = realpath(dirname($targetPath));
        if ($fullTargetPath === false) {
            throw new InvalidArgumentException("Target path $targetPath is invalid.");
        }

        if (file_exists($targetPath)) {
            throw new InvalidArgumentException("Target path $targetPath already exists.");
        }
    }

    protected function copyStreamToPath(string $targetPath): void
    {
        $streamUri = $this->stream->getMetadata('uri');
        empty($streamUri) ?
            $this->copyStreamToPathFromStream($targetPath) :
            $this->copyStreamToPathFromFile($targetPath, $streamUri);
    }

    protected function copyStreamToPathFromStream(string $targetPath): void
    {
        $targetResource = fopen($targetPath, 'w');
        if ($targetResource === false) {
            throw new FileSystemException("Could not open $targetPath.");
        }
        if (fwrite($targetResource, $this->stream->getContents()) === false) {
            throw new FileSystemException("Could not write to $targetPath.");
        }
        if (fclose($targetResource) === false) {
            throw new FileSystemException("Could not close $targetPath.");
        }
    }

    protected function copyStreamToPathFromFile(string $targetPath, string $streamUri): void
    {
        if (!(php_sapi_name() == 'cli' ? rename($streamUri, $targetPath) : is_uploaded_file($streamUri) && move_uploaded_file($streamUri, $targetPath))) {
            throw new FileSystemException("Could not copy $streamUri to $targetPath.");
        }
    }

    /**
     * Retrieve the file size.
     *
     * Implementations SHOULD return the value stored in the "size" key of
     * the file in the $_FILES array if available, as PHP calculates this based
     * on the actual size transmitted.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method MUST return
     * UPLOAD_ERR_OK.
     *
     * Implementations SHOULD return the value stored in the "error" key of
     * the file in the $_FILES array.
     *
     * @see    http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "name" key of
     * the file in the $_FILES array.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
