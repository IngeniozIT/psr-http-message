<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\Stream
 */
class StreamTest extends TestCase
{
    /**
     * After each test, reset functions overrides.
     */
    protected function tearDown(): void
    {
        NativeFunctionsMocker::resetAll();
    }

    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /**
     * Get a generic StreamInterface object.
     */
    protected function getStream(): StreamInterface
    {
        return $this->getStreamWithHandle(fopen('php://temp', 'r+'));
    }

    /**
     * Get a generic StreamInterface object with a specific stream.
     *
     * @param  resource|mixed $handle {@see \fopen}.
     */
    protected function getStreamWithHandle($handle): StreamInterface
    {
        return new \IngeniozIT\Http\Message\Stream($handle);
    }

    /**
     * Open a temporary file and return its resource.
     *
     * @param  string $mode (optional) Mode to use while opening the file.
     * @return resource
     */
    protected function getFileDescriptor(string $mode = 'r+')
    {
        $tmpFd = tmpfile();

        if ($tmpFd === false) {
            throw new \Exception('Could not create temporary file');
        }

        $path = stream_get_meta_data($tmpFd)['uri'];

        if ($mode[0] === 'x') {
            fclose($tmpFd);
            $fd = fopen($path, $mode);
        } else {
            $fd = fopen($path, $mode);
            fclose($tmpFd);
        }

        if ($fd === false) {
            throw new \Exception("Could not open file $path");
        }

        return $fd;
    }

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getStream() return a StreamInterface ?
     */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(StreamInterface::class, $this->getStream());
    }

    /**
     * Call the constructor with a bad argument.
     */
    public function testConstructBadArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getStreamWithHandle('not a handle');
    }

    // ========================================== //
    // To String                                  //
    // ========================================== //

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     */
    public function testToString(): void
    {
        $stream = $this->getStream();
        $this->assertSame('', (string)$stream);

        $stream->write('foo bar baz');
        $stream->rewind();
        $this->assertSame('foo bar baz', (string)$stream);
    }

    /**
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     */
    public function testToStringNoSeek(): void
    {
        $stream = $this->getStream();
        $this->assertSame('', (string)$stream);
        $stream->write('foo bar baz');
        $stream->rewind();
        $stream->seek(4);
        $this->assertSame('bar baz', (string)$stream);
    }

    /**
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     */
    public function testToStringException(): void
    {
        $stream = $this->getStream();
        $stream->write('foo bar baz');
        $stream->rewind();

        NativeFunctionsMocker::makeFunctionReturn('fread', false);
        $this->assertSame('', (string)$stream);
    }

    // ========================================== //
    // Close                                      //
    // ========================================== //

    /**
     * Closes the stream and any underlying resources.
     */
    public function testClose(): void
    {
        $rs = fopen('php://temp', 'r+');
        $stream = $this->getStreamWithHandle($rs);

        $this->assertTrue(is_resource($rs));
        $stream->close();
        $this->assertFalse(is_resource($rs));
    }

    // ========================================== //
    // Detach                                     //
    // ========================================== //

    /**
     * Separates any underlying resources from the stream.
     */
    public function testDetach(): void
    {
        $rs = fopen('php://temp', 'r+');
        $stream = $this->getStreamWithHandle($rs);

        $this->assertTrue(is_resource($rs));

        $rs2 = $stream->detach();
        $this->assertSame($rs, $rs2);

        $this->assertTrue(is_resource($rs));
        $this->assertTrue(is_resource($rs2));
    }

    /**
     * After the stream has been detached, the stream is in an unusable state.
     */
    public function testDetachedStreamIsUnusable(): void
    {
        $stream = $this->getStream();
        $stream->detach();

        $this->assertNull($stream->detach());
        $this->assertNull($stream->getSize());
        try {
            $stream->tell();
            $this->assertTrue(false);
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        $this->assertTrue($stream->eof());
        $this->assertFalse($stream->isSeekable());
        try {
            $stream->seek(2);
            $this->assertTrue(false);
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        try {
            $stream->rewind();
            $this->assertTrue(false);
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        $this->assertFalse($stream->isWritable());
        try {
            $stream->write('foo');
            $this->assertTrue(false);
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        $this->assertFalse($stream->isReadable());
        try {
            $stream->read(42);
            $this->assertTrue(false);
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        try {
            $stream->getContents();
            $this->assertTrue(false);
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        $this->assertSame([], $stream->getMetadata());
    }

    // ========================================== //
    // Get Size                                   //
    // ========================================== //

    /**
     * Get the size of the stream if known.
     */
    public function testGetSize(): void
    {
        $stream = $this->getStream();
        $this->assertSame(0, $stream->getSize());

        $stream->write('foo bar');
        $this->assertSame(7, $stream->getSize());

        $stream->write(' baz');
        $this->assertSame(11, $stream->getSize());

        // Simulate fstat failure
        NativeFunctionsMocker::makeFunctionReturn('fstat', false);
        $this->assertNull($stream->getSize());
    }

    // ========================================== //
    // Tell                                       //
    // ========================================== //

    /**
     * Returns the current position of the file read/write pointer
     */
    public function testTell(): void
    {
        $stream = $this->getStream();
        $this->assertSame(0, $stream->tell());

        $stream->write('foo bar');
        $this->assertSame(7, $stream->tell());

        $stream->rewind();
        $this->assertSame(0, $stream->tell());

        $stream->read(4);
        $this->assertSame(4, $stream->tell());

        $stream->read(4242);
        $this->assertSame(7, $stream->tell());
    }

    /**
     * Tests throws \RuntimeException on error.
     */
    public function testTellException(): void
    {
        $stream = $this->getStream();
        NativeFunctionsMocker::makeFunctionReturn('ftell', false);
        $this->expectException(\RuntimeException::class);
        $stream->tell();
    }

    // ========================================== //
    // Eof                                        //
    // ========================================== //

    /**
     * Returns true if the stream is at the end of the stream.
     */
    public function testEof(): void
    {
        $rs = fopen('php://temp', 'w+');
        $stream = $this->getStreamWithHandle($rs);

        $this->assertFalse($stream->eof());

        $stream->write('foo bar');
        $this->assertFalse($stream->eof());

        $stream->rewind();
        $this->assertFalse($stream->eof());

        $this->assertSame('foo ba', $stream->read(6));
        $this->assertFalse($stream->eof());

        $this->assertSame('r', $stream->read(1));
        $this->assertFalse($stream->eof());

        $this->assertSame('', $stream->read(1));
        $this->assertTrue($stream->eof());

        $this->assertSame('', $stream->read(4242));
        $this->assertTrue($stream->eof());
    }

    // ========================================== //
    // Is Seekable                                //
    // ========================================== //

    /**
     * Returns whether or not the stream is seekable.
     */
    public function testIsSeekable(): void
    {
        $stream = $this->getStream();
        $this->assertTrue($stream->isSeekable());

        NativeFunctionsMocker::makeFunctionReturn(
            'stream_get_meta_data',
            [
                'seekable' => false,
                'mode' => 'r',
            ]
        );
        $stream = $this->getStream();
        $this->assertFalse($stream->isSeekable());
    }

    // ========================================== //
    // Is Writable                                //
    // ========================================== //

    /**
     * Returns whether or not the stream is writable.
     * Write data to the stream. @throws \RuntimeException on failure.
     *
     * @dataProvider getWritableProvider
     * @param        string $mode
     * @param        bool   $shouldBeWritable
     */
    public function testIsWritable(string $mode, bool $shouldBeWritable): void
    {
        $stream = $this->getStreamWithHandle($this->getFileDescriptor($mode));

        if ($shouldBeWritable) {
            $this->assertTrue($stream->isWritable());
            $stream->write('foo');
        } else {
            $this->assertFalse($stream->isWritable());
            $this->expectException(\RuntimeException::class);
            $stream->write('foo');
        }
    }

    /**
     * Provider. Return fopen modes and whether they are writable or not.
     *
     * @return array<array>
     */
    public function getWritableProvider(): array
    {
        return [
            'r' => ['r', false],
            'rb' => ['rb', false],
            'rt' => ['rt', false],

            'r+' => ['r+', true],
            'r+b' => ['r+b', true],
            'r+t' => ['r+t', true],

            'w' => ['w', true],
            'wb' => ['wb', true],
            'wt' => ['wt', true],

            'w+' => ['w+', true],
            'w+b' => ['w+b', true],
            'w+t' => ['w+t', true],

            'a' => ['a', true],
            'ab' => ['ab', true],
            'at' => ['at', true],

            'a+' => ['a+', true],
            'a+b' => ['a+b', true],
            'a+t' => ['a+t', true],

            'x+' => ['x+', true],
            'x+b' => ['x+b', true],
            'x+t' => ['x+t', true],

            'c' => ['c', true],
            'cb' => ['cb', true],
            'ct' => ['ct', true],

            'c+' => ['c+', true],
            'c+b' => ['c+b', true],
            'c+t' => ['c+t', true],
        ];
    }

    // ========================================== //
    // Write                                      //
    // ========================================== //

    /**
     * Write data to the stream. @throws \RuntimeException on failure.
     */
    public function testWriteFilesystemError(): void
    {
        $stream = $this->getStream();

        NativeFunctionsMocker::makeFunctionReturn('fwrite', false);
        $this->expectException(\RuntimeException::class);
        $stream->write('foo');
    }

    // ========================================== //
    // Is Readable                                //
    // ========================================== //

    /**
     * Returns whether or not the stream is readable.
     * Read data from the stream. @throws \RuntimeException on failure.
     *
     * @dataProvider getReadableProvider
     * @param        string $mode
     * @param        bool   $shouldBeReadable
     */
    public function testIsReadable(string $mode, bool $shouldBeReadable): void
    {
        $stream = $this->getStreamWithHandle($this->getFileDescriptor($mode));

        if ($shouldBeReadable) {
            $this->assertTrue($stream->isReadable());
            $this->assertSame('', $stream->read(42));
        } else {
            $this->assertFalse($stream->isReadable());
            $this->expectException(\RuntimeException::class);
            $stream->read(42);
        }
    }

    /**
     * Provider. Return fopen modes and whether they are readable or not.
     *
     * @return array<array>
     */
    public function getReadableProvider(): array
    {
        return [
            'r' => ['r', true],
            'rb' => ['rb', true],
            'rt' => ['rt', true],

            'r+' => ['r+', true],
            'r+b' => ['r+b', true],
            'r+t' => ['r+t', true],

            'w' => ['w', false],
            'wb' => ['wb', false],
            'wt' => ['wt', false],

            'w+' => ['w+', true],
            'w+b' => ['w+b', true],
            'w+t' => ['w+t', true],

            'a' => ['a', false],
            'ab' => ['ab', false],
            'at' => ['at', false],

            'a+' => ['a+', true],
            'a+b' => ['a+b', true],
            'a+t' => ['a+t', true],

            'x+' => ['x+', true],
            'x+b' => ['x+b', true],
            'x+t' => ['x+t', true],

            'c' => ['c', false],
            'cb' => ['cb', false],
            'ct' => ['ct', false],

            'c+' => ['c+', true],
            'c+b' => ['c+b', true],
            'c+t' => ['c+t', true],
        ];
    }

    /**
     * @throws \RuntimeException if an error occurs.
     */
    public function testReadWithNegativeNumber(): void
    {
        $stream = $this->getStream();

        $this->expectException(\RuntimeException::class);
        $stream->read(-1);
    }

    // ========================================== //
    // Read                                       //
    // ========================================== //

    /**
     * Read data from the stream. @throws \RuntimeException if an error occurs.
     */
    public function testReadFilesystemError(): void
    {
        $stream = $this->getStream();

        NativeFunctionsMocker::makeFunctionReturn('fread', false);
        $this->expectException(\RuntimeException::class);
        $stream->read(42);
    }

    /**
     * Read data from the stream. @throws \RuntimeException if an error occurs.
     */
    public function testReadNegativeLength(): void
    {
        $stream = $this->getStream();

        $this->expectException(\RuntimeException::class);
        $stream->read(-1);
    }

    // ========================================== //
    // Get Metadata                               //
    // ========================================== //

    public function testGetMetadata(): void
    {
        $fd = $this->getFileDescriptor('r+');
        $stream = $this->getStreamWithHandle($fd);

        $this->assertSame(stream_get_meta_data($fd), $stream->getMetadata());
    }
}
