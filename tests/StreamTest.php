<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use IngeniozIT\Http\Message\Stream;
use IngeniozIT\Http\Message\Exception\{DetachedStreamException, InvalidResourceOperationException, IOException};
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StreamTest extends TestCase
{
    use WithTempFiles;

    public function testIsAPsrStream(): void
    {
        $resource = $this->open('php://temp', 'r');
        $stream = new Stream($resource);

        self::assertInstanceOf(StreamInterface::class, $stream);
    }

    /**
     * @suppress PhanNoopNew
     */
    public function testNeedsAResource(): void
    {
        $resource = $this->open('php://temp', 'r');
        fclose($resource);

        self::expectException(InvalidArgumentException::class);
        new Stream($resource);
    }

    public function testCanBeClosed(): void
    {
        $resource = $this->open('php://temp', 'r');
        $stream = new Stream($resource);

        $stream->close();

        self::assertFalse(is_resource($stream->detach()));
    }

    public function testClosesResourceOnDestruct(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);

        unset($stream);

        self::assertFalse(is_resource($resource));
    }

    public function testCanDetachResource(): void
    {
        $resource = $this->open('php://temp', 'r');
        $stream = new Stream($resource);

        $detachedResource = $stream->detach();

        self::assertSame($resource, $detachedResource);
    }

    public function testDetachedStreamIsUnusable(): void
    {
        $resource = $this->open('php://temp', 'r');
        $stream = new Stream($resource);
        $stream->detach();

        $detachedResource = $stream->detach();
        $metadata = $stream->getMetadata();
        $isReadable = $stream->isReadable();
        $isWritable = $stream->isWritable();
        $size = $stream->getSize();
        $eof = $stream->eof();
        $content = (string) $stream;
        $seekable = $stream->isSeekable();

        self::assertNull($detachedResource);
        self::assertEquals([], $metadata);
        self::assertFalse($isReadable);
        self::assertFalse($isWritable);
        self::assertNull($size);
        self::assertTrue($eof);
        self::assertEquals('', $content);
        self::assertFalse($seekable);
    }

    /**
     * @dataProvider providerDetachedInvalidOperations
     */
    public function testSomeOperationsThrowExceptionWhenStreamIsDetached(callable $operation): void
    {
        $resource = $this->open('php://temp', 'r');
        $stream = new Stream($resource);
        $stream->detach();

        self::expectException(DetachedStreamException::class);
        $operation($stream);
    }

    /**
     * @return array<string, array{operation: callable}>
     */
    public static function providerDetachedInvalidOperations(): array
    {
        return [
            'read' => ['operation' => fn(Stream $stream) => $stream->read(1)],
            'write' => ['operation' => fn(Stream $stream) => $stream->write('foo')],
            'rewind' => ['operation' => fn(Stream $stream) => $stream->rewind()],
            'tell' => ['operation' => fn(Stream $stream) => $stream->tell()],
            'seek' => ['operation' => fn(Stream $stream) => $stream->seek(0)],
            'getContents' => ['operation' => fn(Stream $stream) => $stream->getContents()],
        ];
    }

    public function testCanBeConvertedToString(): void
    {
        $resource = $this->open('php://temp', 'w+');
        fwrite($resource, 'foo');
        $stream = new Stream($resource);

        $content = (string) $stream;

        self::assertEquals('foo', $content);
    }

    public function testEqualsEmptyStringWhenStreamIsNotReadable(): void
    {
        $resource = $this->open(self::$file, 'w');
        fwrite($resource, 'foo');
        $stream = new Stream($resource);

        $content = (string) $stream;

        self::assertEquals('', $content);
    }

    public function testCanGetStreamSize(): void
    {
        $resource = $this->open('php://temp', 'w+');
        fwrite($resource, '12345678');
        $stream = new Stream($resource);

        $size = $stream->getSize();
        self::assertEquals(8, $size);

        fwrite($resource, 'foo');
        $size = $stream->getSize();
        self::assertEquals(11, $size);
    }

    public function testCanTellPointerPosition(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);

        $position = $stream->tell();
        self::assertEquals(0, $position);

        fwrite($resource, '12345678');
        $position = $stream->tell();
        self::assertEquals(8, $position);
    }

    public function testThrowsExceptionOnTellError(): void
    {
        $stream = new Stream(STDIN);

        self::expectException(IOException::class);
        $stream->tell();
    }

    /**
     * @dataProvider providerEndOfFile
     */
    public function testCanCheckEndOfFile(StreamInterface $stream, bool $endOfFile): void
    {
        $eof = $stream->eof();

        self::assertEquals($endOfFile, $eof);
    }

    /**
     * @return array<string, array{stream: StreamInterface, endOfFile: bool}>
     */
    public static function providerEndOfFile(): array
    {
        $resource1 = self::open('php://temp', 'w+');
        fwrite($resource1, '12345678');
        fseek($resource1, 0);

        $resource2 = self::open('php://temp', 'w+');
        fwrite($resource2, '12345678');
        fseek($resource2, 0);
        fread($resource2, 8);

        $resource3 = self::open('php://temp', 'w+');
        fwrite($resource3, '12345678');
        fseek($resource3, 0);
        fread($resource3, 9);

        $resource = self::open('php://temp', 'w+');
        $detachedStream = new Stream($resource);
        $detachedStream->detach();

        return [
            'first character' => [
                'stream' => new Stream($resource1),
                'endOfFile' => false,
            ],
            'last character' => [
                'stream' => new Stream($resource2),
                'endOfFile' => false,
            ],
            'end of file' => [
                'stream' => new Stream($resource3),
                'endOfFile' => true,
            ],
            'detached stream' => [
                'stream' => $detachedStream,
                'endOfFile' => true,
            ],
        ];
    }

    public function testCanTellIfStreamIsSeekable(): void
    {
        $resource = $this->open('php://temp', 'r');
        $stream = new Stream($resource);

        self::assertTrue($stream->isSeekable());
    }

    public function testCanTellIfStreamIsNonSeekable(): void
    {
        $resource = $this->nonSeekableResource();
        $stream = new Stream($resource);

        self::assertFalse($stream->isSeekable());
    }

    /**
     * @dataProvider providerSeek
     */
    public function testCanSeekStream(int $firstOffset, int $seekOffset, int $whence, int $expectedOffset): void
    {
        $resource = $this->open('php://temp', 'r+');
        fwrite($resource, '12345678');
        fseek($resource, $firstOffset);
        $stream = new Stream($resource);

        $stream->seek($seekOffset, $whence);
        $offset = ftell($resource);

        self::assertEquals($expectedOffset, $offset);
    }

    /**
     * @return array<string, array{firstOffset: int, seekOffset: int, whence: int, expectedOffset: int}>
     */
    public static function providerSeek(): array
    {
        return [
            'SEEK_SET start of stream' => [
                'firstOffset' => 0,
                'seekOffset' => 2,
                'whence' => SEEK_SET,
                'expectedOffset' => 2,
            ],
            'SEEK_SET middle of stream' => [
                'firstOffset' => 4,
                'seekOffset' => 2,
                'whence' => SEEK_SET,
                'expectedOffset' => 2,
            ],
            'SEEK_CUR start of stream' => [
                'firstOffset' => 0,
                'seekOffset' => 2,
                'whence' => SEEK_CUR,
                'expectedOffset' => 2,
            ],
            'SEEK_CUR middle of stream' => [
                'firstOffset' => 4,
                'seekOffset' => 2,
                'whence' => SEEK_CUR,
                'expectedOffset' => 6,
            ],
            'SEEK_END' => [
                'firstOffset' => 4,
                'seekOffset' => 0,
                'whence' => SEEK_END,
                'expectedOffset' => 8,
            ],
        ];
    }

    public function testSeekThrowsExceptionWhenStreamIsNotSeekable(): void
    {
        $resource = $this->nonSeekableResource();
        $stream = new Stream($resource);

        self::expectException(InvalidResourceOperationException::class);
        $stream->seek(0);
    }

    public function testCanRewindStream(): void
    {
        $resource = $this->open('php://temp', 'r+');
        fwrite($resource, '12345678');
        fseek($resource, 4);
        $stream = new Stream($resource);

        $stream->rewind();
        $offset = ftell($resource);

        self::assertEquals(0, $offset);
    }

    public function testRewindThrowsExceptionWhenStreamIsNotSeekable(): void
    {
        $resource = $this->nonSeekableResource();
        $stream = new Stream($resource);

        self::expectException(InvalidResourceOperationException::class);
        $stream->rewind();
    }

    /**
     * @dataProvider providerWriteAccessModes
     */
    public function testCanTellIfStreamIsWritable(string $mode, bool $expected): void
    {
        $resource = $this->open($mode[0] === 'x' ? self::$availableFile : self::$file, $mode);
        $stream = new Stream($resource);

        $isWritable = $stream->isWritable();

        self::assertEquals($expected, $isWritable);

        $stream->close();
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function providerWriteAccessModes(): array
    {
        return [
            'r' => ['r', false],
            'r+' => ['r+', true],
            'w' => ['w', true],
            'w+' => ['w+', true],
            'a' => ['a', true],
            'a+' => ['a+', true],
            'x' => ['x', true],
            'x+' => ['x+', true],
            'c' => ['c', true],
            'c+' => ['c+', true],
        ];
    }

    public function testCanWriteContentToStream(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);

        $stream->write('foo');
        fseek($resource, 0);
        $content = fgets($resource);

        self::assertEquals('foo', $content);
    }

    public function testWriteThrowsExceptionWhenStreamIsNotWritable(): void
    {
        $resource = $this->open(self::$file, 'r');
        $stream = new Stream($resource);

        self::expectException(InvalidResourceOperationException::class);
        $stream->write('foo');
    }

    public function testWriteThrowsExceptionWhenFwriteFails(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);
        fclose($resource);

        self::expectException(IOException::class);
        $stream->write('foo');
    }

    /**
     * @dataProvider providerReadAccessModes
     */
    public function testCanTellIfStreamIsReadable(string $mode, bool $expected): void
    {
        $resource = $this->open($mode[0] === 'x' ? self::$availableFile : self::$file, $mode);
        $stream = new Stream($resource);

        $isReadable = $stream->isReadable();

        self::assertEquals($expected, $isReadable);

        $stream->close();
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function providerReadAccessModes(): array
    {
        return [
            'r' => ['r', true],
            'r+' => ['r+', true],
            'w' => ['w', false],
            'w+' => ['w+', true],
            'a' => ['a', false],
            'a+' => ['a+', true],
            'x' => ['x', false],
            'x+' => ['x+', true],
            'c' => ['c', false],
            'c+' => ['c+', true],
        ];
    }

    public function testCanReadContentFromStream(): void
    {
        $resource = $this->open('php://temp', 'w+');
        fwrite($resource, 'foo');
        fseek($resource, 0);
        $stream = new Stream($resource);

        $content = $stream->read(3);

        self::assertEquals('foo', $content);
    }

    public function testCanReadEmptyContent(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);

        $content = $stream->read(3);

        self::assertEquals('', $content);
    }

    public function testReadThrowsExceptionWhenStreamIsNotReadable(): void
    {
        $resource = $this->open(self::$file, 'w');
        $stream = new Stream($resource);

        self::expectException(InvalidResourceOperationException::class);
        $stream->read(0);
    }

    public function testReadThrowsExceptionWhenReadingANegativeAmountOfCharacters(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);
        fclose($resource);

        self::expectException(InvalidArgumentException::class);
        $stream->read(-1);
    }

    public function testReadThrowsExceptionWhenFreadFails(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);
        fclose($resource);

        self::expectException(IOException::class);
        $stream->read(1);
    }

    public function testCanReadRemainingContent(): void
    {
        $resource = $this->open('php://temp', 'w+');
        fwrite($resource, 'foobar');
        fseek($resource, 3);
        $stream = new Stream($resource);

        $content = $stream->getContents();

        self::assertEquals('bar', $content);
    }

    public function testHasMetadata(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);

        /** @var array<string, mixed> $metadata */
        $metadata = $stream->getMetadata();

        self::assertEquals('php://temp', $metadata['uri'] ?? null);
    }

    public function testCanGetMetadataKey(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);

        $metadataUri = $stream->getMetadata('uri');

        self::assertEquals('php://temp', $metadataUri);
    }

    public function testCannotAccessEmptyMetadataKey(): void
    {
        $resource = $this->open('php://temp', 'w+');
        $stream = new Stream($resource);

        $metadataUri = $stream->getMetadata('foo');

        self::assertNull($metadataUri);
    }
}
