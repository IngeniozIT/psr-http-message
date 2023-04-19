<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use IngeniozIT\Http\Message\StreamFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

class StreamFactoryTest extends TestCase
{
    use WithTempFiles;

    public function testIsAPsrStreamFactory(): void
    {
        $streamFactory = new StreamFactory();

        self::assertInstanceOf(StreamFactoryInterface::class, $streamFactory);
    }

    public function testCanCreateAStreamFromAString(): void
    {
        $streamFactory = new StreamFactory();

        $stream = $streamFactory->createStream('12345678');
        $content = $stream->getContents();

        self::assertEquals('12345678', $content);
    }

    public function testStreamIsCreatedWithATemporaryResource(): void
    {
        $streamFactory = new StreamFactory();

        $stream = $streamFactory->createStream();
        $streamType = $stream->getMetadata('stream_type');

        self::assertEquals('TEMP', $streamType);
    }

    public function testCanCreateAStreamFromAFile(): void
    {
        $streamFactory = new StreamFactory();

        $stream = $streamFactory->createStreamFromFile(self::$file, 'r+');
        $uri = $stream->getMetadata('uri');
        $mode = $stream->getMetadata('mode');

        self::assertEquals(self::$file, $uri);
        self::assertEquals('r+', $mode);
    }

    public function testFileMustBeReadable(): void
    {
        $streamFactory = new StreamFactory();

        self::expectException(RuntimeException::class);
        $streamFactory->createStreamFromFile('/', 'r+');
    }

    public function testFileAccessModeMustBeValid(): void
    {
        $streamFactory = new StreamFactory();

        self::expectException(InvalidArgumentException::class);
        $streamFactory->createStreamFromFile('php://temp', 'invalidMode');
    }

    public function testCanCreateAStreamFromAResource(): void
    {
        $resource = $this->open('php://temp', 'r');
        $streamFactory = new StreamFactory();

        $stream = $streamFactory->createStreamFromResource($resource);
        $streamResource = $stream->detach();

        self::assertSame($resource, $streamResource);
    }

    public function testResourceMustBeReadable(): void
    {
        $resource = $this->open(self::$file, 'w');
        $streamFactory = new StreamFactory();

        self::expectException(InvalidArgumentException::class);
        $streamFactory->createStreamFromResource($resource);
    }
}
