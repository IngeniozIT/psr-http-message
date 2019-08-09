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
    public static $fstat = false;
    public static $ftell = false;
    public static $seekable = true;

    protected function getStream()
    {
        return $this->getStreamWithHandle(fopen('php://temp', 'r+'));
    }

    protected function getStreamWithHandle($handle)
    {
        return new \IngeniozIT\Http\Message\Stream($handle);
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(StreamInterface::class, $this->getStream());
    }

    // ========================================== //
    // TO STRING                                  //
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

    // ========================================== //
    // CLOSE                                      //
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
    // DETACH                                     //
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
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        $this->assertTrue($stream->eof());
        $this->assertFalse($stream->isSeekable());
        try {
            $stream->seek(2);
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        try {
            $stream->rewind();
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        $this->assertFalse($stream->isWritable());
        try {
            $stream->write('foo');
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        $this->assertFalse($stream->isReadable());
        try {
            $stream->read(42);
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        try {
            $stream->getContents();
        } catch (\RunTimeException $e) {
            $this->assertTrue(true);
        }
        $this->assertSame([], $stream->getMetadata());
    }

    // ========================================== //
    // GET SIZE                                   //
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
    // TELL                                       //
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
    // EOF                                        //
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
// FILESYSTEM OVERRIDES                       //
// ========================================== //

namespace IngeniozIT\Http\Message;

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
