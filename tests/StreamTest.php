<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Tests\Message;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\Stream
 */
class StreamTest extends TestCase
{
    /** @var bool True to make fstat fail. */
    public static $fstat = false;
    /** @var bool True to make ftell fail. */
    public static $ftell = false;
    /** @var bool True to make fread fail. */
    public static $fread = false;
    /** @var bool True to make fwrite fail. */
    public static $fwrite = false;
    /** @var bool False to make a stream unseekable. */
    public static $seekable = true;

    protected function setUp(): void
    {
        self::$fstat = false;
        self::$ftell = false;
        self::$fread = false;
        self::$fwrite = false;
        self::$seekable = true;
    }

    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    protected function getStreamWithHandle($handle)
    {
        return new \IngeniozIT\Http\Message\Stream($handle);
    }

    protected function getStream()
    {
        return $this->getStreamWithHandle(fopen('php://temp', 'r+'));
    }

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getStream() return a StreamInterface ?
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(StreamInterface::class, $this->getStream());
    }

    /**
     * Call the constructor with a bad argument.
     */
    public function testConstructBadArgument()
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
    public function testToString()
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
    public function testToStringNoSeek()
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
    public function testToStringException()
    {
        $stream = $this->getStream();
        $stream->write('foo bar baz');
        $stream->rewind();

        self::$fread = true;
        $this->assertSame('', (string)$stream);
    }

    // ========================================== //
    // Close                                      //
    // ========================================== //

    /**
     * Closes the stream and any underlying resources.
     */
    public function testClose()
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
    public function testDetach()
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
    public function testDetachedStreamIsUnusable()
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
    public function testGetSize()
    {
        $stream = $this->getStream();
        $this->assertSame(0, $stream->getSize());

        $stream->write('foo bar');
        $this->assertSame(7, $stream->getSize());

        $stream->write(' baz');
        $this->assertSame(11, $stream->getSize());

        // Simulate fstat failure
        self::$fstat = true;
        $this->assertNull($stream->getSize());
        self::$fstat = false;
    }

    // ========================================== //
    // Tell                                       //
    // ========================================== //

    /**
     * Returns the current position of the file read/write pointer
     */
    public function testTell()
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
    public function testTellException()
    {
        $stream = $this->getStream();
        self::$ftell = true;
        $this->expectException(\RuntimeException::class);
        $stream->tell();
    }

    // ========================================== //
    // Eof                                        //
    // ========================================== //

    /**
     * Returns true if the stream is at the end of the stream.
     */
    public function testEof()
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

    /**
     * Returns whether or not the stream is seekable.
     */
    public function testIsSeekable()
    {
        $stream = $this->getStream();
        $this->assertTrue($stream->isSeekable());

        self::$seekable = false;
        $stream = $this->getStream();
        $this->assertFalse($stream->isSeekable());
        self::$seekable = true;
    }
}

// ========================================== //
// Filesystem overrides                       //
// ========================================== //

namespace IngeniozIT\Http\Message;

function fread($resource, $length)
{
    return \IngeniozIT\Http\Tests\Message\StreamTest::$fread ? false : \fread($resource, $length);
}

function fwrite($resource, $string)
{
    return \IngeniozIT\Http\Tests\Message\StreamTest::$fwrite ? false : \fwrite($resource, $string);
}

function fstat($resource)
{
    return \IngeniozIT\Http\Tests\Message\StreamTest::$fstat ? [] : \fstat($resource);
}

function ftell($resource)
{
    return \IngeniozIT\Http\Tests\Message\StreamTest::$ftell ? false : \ftell($resource);
}

function stream_get_meta_data($resource)
{
    return !\IngeniozIT\Http\Tests\Message\StreamTest::$seekable ? ['seekable' => false, 'mode' => 'r'] : \stream_get_meta_data($resource);
}
