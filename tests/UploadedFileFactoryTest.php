<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{UploadedFileFactoryInterface, UploadedFileInterface};
use IngeniozIT\Http\Message\{StreamFactory, UploadedFileFactory};
use InvalidArgumentException;

class UploadedFileFactoryTest extends TestCase
{
    use WithTempFiles;

    public function testIsAPsrUploadedFileFactory(): void
    {
        $uploadedFileFactory = new UploadedFileFactory(new StreamFactory());

        self::assertInstanceOf(UploadedFileFactoryInterface::class, $uploadedFileFactory);
    }

    public function testCreatesUploadedFile(): void
    {
        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStreamFromFile(self::$file);
        $uploadedFileFactory = new UploadedFileFactory($streamFactory);

        $uploadedFile = $uploadedFileFactory->createUploadedFile(
            $stream,
            42,
            UPLOAD_ERR_EXTENSION,
            'clientFileName',
            'clientMediaType',
        );

        self::assertInstanceOf(UploadedFileInterface::class, $uploadedFile);
        self::assertEquals($stream, $uploadedFile->getStream());
        self::assertEquals(42, $uploadedFile->getSize());
        self::assertEquals(UPLOAD_ERR_EXTENSION, $uploadedFile->getError());
        self::assertEquals('clientFileName', $uploadedFile->getClientFilename());
        self::assertEquals('clientMediaType', $uploadedFile->getClientMediaType());
    }

    public function testThrowsExceptionIfStreamIsNotReadable(): void
    {
        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStreamFromFile(self::$file, 'w');
        $uploadedFileFactory = new UploadedFileFactory($streamFactory);

        self::expectException(InvalidArgumentException::class);
        $uploadedFileFactory->createUploadedFile(
            $stream,
            42,
            UPLOAD_ERR_EXTENSION,
            'clientFileName',
            'clientMediaType',
        );
    }
}
