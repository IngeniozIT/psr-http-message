<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Http\Message\{StreamFactory, UploadedFile};
use Psr\Http\Message\{StreamFactoryInterface, StreamInterface, UploadedFileInterface,};
use RuntimeException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UploadedFileTest extends TestCase
{
    use WithTempFiles;

    public function testIsAPsrUploadedFile(): void
    {
        $uploadedFile = $this->createSubject();

        self::assertInstanceOf(UploadedFileInterface::class, $uploadedFile);
    }

    public function testHasADataStream(): void
    {
        $stream = $this->getStream();
        $uploadedFile = $this->createSubject(stream: $stream);

        $fileStream = $uploadedFile->getStream();

        self::assertSame($stream, $fileStream);
    }

    public function testStreamCanBeMovedToAnotherLocation(): void
    {
        $stream = $this->getStream();
        $streamContent = (string) $stream;
        $uploadedFile = $this->createSubject(
            stream: $stream
        );

        $uploadedFile->moveTo(self::$availableFile);
        $fileContent = file_get_contents(self::$availableFile);

        self::assertEquals($streamContent, $fileContent);
    }

    public function testOriginalStreamIsRemovedOnCompletion(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile(self::$file);
        $uploadedFile = $this->createSubject(
            stream: $stream
        );

        $uploadedFile->moveTo(self::$availableFile);

        self::assertFileDoesNotExist(self::$file);
        self::assertNull($stream->detach());
        self::expectException(RuntimeException::class);
        $uploadedFile->getStream();
    }

    public function testStreamCannotBeMovedToAnInvalidPath(): void
    {
        $uploadedFile = $this->createSubject();

        self::expectException(RuntimeException::class);
        $uploadedFile->moveTo('/');
    }

    public function testStreamCannotBeMovedWhenItHasAlreadyBeenMoved(): void
    {
        $uploadedFile = $this->createSubject();
        $uploadedFile->moveTo(self::$availableFile);

        self::expectException(RuntimeException::class);
        $uploadedFile->moveTo(self::$availableFile);
    }

    public function testCanHaveASize(): void
    {
        $uploadedFile = $this->createSubject(
            size: 42,
        );

        $size = $uploadedFile->getSize();

        self::assertEquals(42, $size);
    }

    public function testSizeCanBeNull(): void
    {
        $uploadedFile = $this->createSubject(
            size: null,
        );

        $size = $uploadedFile->getSize();

        self::assertNull($size);
    }

    public function testHasAnErrorCode(): void
    {
        $uploadedFile = $this->createSubject(
            error: UPLOAD_ERR_PARTIAL,
        );

        $error = $uploadedFile->getError();

        self::assertEquals(UPLOAD_ERR_PARTIAL, $error);
    }

    public function testCanHaveAClientFilename(): void
    {
        $uploadedFile = $this->createSubject(
            clientFilename: 'foo.bar',
        );

        $filename = $uploadedFile->getClientFilename();

        self::assertEquals('foo.bar', $filename);
    }

    public function testClientFilenameCanBeNull(): void
    {
        $uploadedFile = $this->createSubject(
            clientFilename: null,
        );

        $filename = $uploadedFile->getClientFilename();

        self::assertNull($filename);
    }

    public function testCanHaveAClientMediaType(): void
    {
        $uploadedFile = $this->createSubject(
            clientMediaType: 'foo.bar',
        );

        $mediaType = $uploadedFile->getClientMediaType();

        self::assertEquals('foo.bar', $mediaType);
    }

    public function testClientMediaTypeCanBeNull(): void
    {
        $uploadedFile = $this->createSubject(
            clientMediaType: null,
        );

        $mediaType = $uploadedFile->getClientMediaType();

        self::assertNull($mediaType);
    }

    protected function getStream(): StreamInterface
    {
        $factory = new StreamFactory();
        return $factory->createStream('12345678');
    }

    public function createSubject(
        ?StreamInterface $stream = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null,
    ): UploadedFileInterface {
        return new UploadedFile(
            stream: $stream ?? $this->getStream(),
            streamFactory: $streamFactory ?? new StreamFactory(),
            size: $size ?? null,
            error: $error,
            clientFilename: $clientFilename ?? null,
            clientMediaType: $clientMediaType ?? null,
        );
    }
}
