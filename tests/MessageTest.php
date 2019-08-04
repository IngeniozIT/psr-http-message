<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use IngeniozIT\Http\Message\Exceptions\InvalidArgumentException;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\Message
 */
class MessageTest extends TestCase
{
    /**
     * Implementation's default HTTP protocol.
     * @var string
     */
    protected $defaultProtocolVersion = '1.1';

    /**
     * Get a new MessageInterface instance.
     * @return MessageInterface
     */
    protected function getMessage()
    {
        $mockStreamInterface = $this->getMockBuilder(StreamInterface::class)->getMock();
        return new \IngeniozIT\Http\Message\Message($mockStreamInterface);
    }

    /**
     * Does getMessage() return a MessageInterface ?
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(MessageInterface::class, $this->getMessage());
    }

    // ------------------------------------------
    // Protocol version
    // ------------------------------------------

    /**
     * getProcotolVersion classic usage.
     * @dataProvider getProcotolVersionProvider
     * @param  mixed  $newProtocol Protocol to give to the Message. null to
     * disable giving a new protocol version.
     * @param  string $expectedProtocol Expected protocol value.
     */
    public function testProcotolVersion($newProtocol, $expectedProtocol)
    {
        $message = $this->getMessage();

        if ($newProtocol === null) {
            $message2 = $message;
        } else {
            $message2 = $message->withProtocolVersion($newProtocol);
        }

        $protocol = $message2->getProtocolVersion();

        // Retrieves the HTTP protocol version as a string.
        $type = gettype($protocol);
        $this->assertTrue(is_string($protocol), "Protocol version must be string, {$type} given");

        // The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
        $this->assertRegExp('/^\d+(\.\d+)?$/', $protocol, "Protocol version MUST contain only the HTTP version number (e.g., \"1.1\", \"1.0\")");

        // The protocol version MUST match the expected value
        $this->assertSame($expectedProtocol, $protocol, "Expected protocol version '{$expectedProtocol}', got '{$protocol}' instead.");

        if ($expectedProtocol !== $this->defaultProtocolVersion) {
            // This method MUST be implemented in such a way as to retain the
            // immutability of the message, and MUST return an instance that has the
            // new protocol version.
            $this->assertNotSame($message, $message2);
        } else {
            // If nothing gets updated, the same instance is returned
            $this->assertSame($message, $message2);
        }
    }

    /**
     * Provider. Gives input protocol versions and the expected formatted value.
     */
    public function getProcotolVersionProvider()
    {
        return [
            'default protocol' => [null, $this->defaultProtocolVersion],
            '(string)default protocol' => [(string)$this->defaultProtocolVersion, $this->defaultProtocolVersion],
            '(float)default protocol' => [(float)$this->defaultProtocolVersion, $this->defaultProtocolVersion],
            '(int)1' => [1, '1'],
            '(string)1.0' => ['1.0', '1.0'],
            '(float)1.0' => [1.0, '1'],
            '(string)1.1' => ['1.1', '1.1'],
            '(float)1.1' => [1.1, '1.1'],
        ];
    }

    /**
     * getProcotolVersion with invalid values.
     * @dataProvider getProcotolVersionInvalidVersionProvider
     * @param  mixed  $newProtocol Protocol to give to the Message.
     */
    public function testProcotolVersionExceptions($newProtocol)
    {
        $message = $this->getMessage();

        // Throw exception on invalid protocol version.
        $this->expectException(InvalidArgumentException::class);
        $message2 = $message->withProtocolVersion($newProtocol);
    }

    /**
     * Provider. Gives invalid protocol versions.
     */
    public function getProcotolVersionInvalidVersionProvider()
    {
        return [
            'full http version HTTP/2.0' => ['HTTP/2.0'],
            '(string)weird value' => ['test'],
            '(bool)weird string' => [false],
            '(array)weird string' => [[]],
            '(array2)weird string' => [['test']],
        ];
    }

    // ------------------------------------------
    // Headers
    // ------------------------------------------

    public function testHeadersBasic()
    {
        $message = $this->getMessage();

        $message2 = $message->withHeader('foo', 'bar');

        // Retrieves all message header values.
        $this->assertSame(['foo' => ['bar']], $message2->getHeaders());
        // Checks if a header exists
        $this->assertTrue($message2->hasHeader('foo'));
        // Retrieves a message header value
        $this->assertSame(['bar'], $message2->getHeader('foo'));
        // Retrieves a comma-separated string of the values for a single header
        $this->assertSame('bar', $message2->getHeaderLine('foo'));
        // This method MUST be implemented in such a way as to retain the
        // immutability of the message, and MUST return an instance that has the
        // new and/or updated header and value.
        $this->assertNotSame($message, $message2);

        $message3 = $message2->withAddedHeader('foo', 'baz');

        // Retrieves all message header values.
        $this->assertSame(['foo' => ['bar', 'baz']], $message3->getHeaders());
        // Checks if a header exists
        $this->assertTrue($message3->hasHeader('foo'));
        // Retrieves a message header value
        $this->assertSame(['bar', 'baz'], $message3->getHeader('foo'));
        // Retrieves a comma-separated string of the values for a single header
        $this->assertSame('bar,baz', $message3->getHeaderLine('foo'));
        // This method MUST be implemented in such a way as to retain the
        // immutability of the message, and MUST return an instance that has the
        // new and/or updated header and value.
        $this->assertNotSame($message2, $message3);

        // If nothing gets updated, the same instance is returned
        $message4 = $message3->withHeader('foo', ['bar', 'baz']);
        $this->assertSame($message3, $message4);
    }

    /**
     * getHeaders with empty headers.
     */
    public function testHeadersEmpty()
    {
        // Empty message
        $message = $this->getMessage();

        // Retrieves all message header values.
        $this->assertSame([], $message->getHeaders());
        // Checks if a header exists
        $this->assertFalse($message->hasHeader('foo'));
        // Retrieves a message header value
        $this->assertSame([], $message->getHeader('foo'));
        // Retrieves a comma-separated string of the values for a single header
        $this->assertSame('', $message->getHeaderLine('foo'));

        // Filled + emptied message
        $message2 = $message
        ->withHeader('foo', 'foo')
        ->withoutHeader('foo')
        ->withHeader('bar', 'bar')
        ->withAddedHeader('bar', 'baz')
        ->withoutHeader('bar')
        ->withoutHeader('bar');

        // Retrieves all message header values.
        $this->assertSame([], $message2->getHeaders());
        // Checks if a header exists
        $this->assertFalse($message2->hasHeader('foo'));
        // Retrieves a message header value
        $this->assertSame([], $message2->getHeader('foo'));
        // Retrieves a comma-separated string of the values for a single header
        $this->assertSame('', $message2->getHeaderLine('foo'));

        $this->assertNotSame($message, $message2);
    }

    /**
     * headers classic usage.
     * @dataProvider getHeadersValuesProvider
     * @param  mixed  $newValue Header to give to the Message.
     * @param  array $expectedValue Expected header value.
     */
    public function testHeadersValues($newValue, $expectedValue)
    {
        $message = $this->getMessage()->withHeader('foo', $newValue);

        $value = $message->getHeader('foo');

        // Values must be correctly converted to string[]
        $this->assertSame($expectedValue, $value);
    }

    /**
     * Provider. Gives input protocol versions and the expected formatted value.
     */
    public function getHeadersValuesProvider()
    {
        return [
            '(string)' => ['value', ['value']],
            '(string[])' => [['value1', 'value2'], ['value1', 'value2']],
            '(int)' => [42, ['42']],
            '(int[])' => [[42, -42], ['42', '-42']],
            '(float)' => [42.42, ['42.42']],
            '(float[])' => [[42.42, -42.42], ['42.42', '-42.42']],
        ];
    }

    /**
     * headers with invalid values.
     * @dataProvider getHeadersInvalidValuesProvider
     * @param  mixed  $value Value to give to the header.
     */
    public function testHeadersInvalidValues($value)
    {
        $message = $this->getMessage();

        // Throws exception on invalid header value
        $this->expectException(InvalidArgumentException::class);
        $message2 = $message->withHeader('test', [$value]);
    }

    /**
     * headers with invalid names.
     * @dataProvider getHeadersInvalidNamesProvider
     * @param  mixed  $name Name to give to the header.
     */
    public function testHeadersInvalidNames($name)
    {
        $message = $this->getMessage();

        // Throws exception on invalid header value
        $this->expectException(InvalidArgumentException::class);
        $message2 = $message->withHeader($name, 'test');
    }

    /**
     * Provider. Gives invalid header values.
     */
    public function getHeadersInvalidValuesProvider()
    {
        return [
            '(array)' => [['test' => 1]],
            '(object)' => [(object)['test' => 1]],
        ];
    }

    /**
     * Provider. Gives invalid header names.
     */
    public function getHeadersInvalidNamesProvider()
    {
        return $this->getHeadersInvalidValuesProvider();
    }

    /**
     * manipulate a header's case-insensitivity
     */
    public function testHeadersCaseInsensitive()
    {
        $message = $this->getMessage()->withHeader('FOO', 'BAR');

        // Retrieves all message header values.
        $this->assertSame(['FOO' => ['BAR']], $message->getHeaders());
        // Checks if a header exists
        $this->assertTrue($message->hasHeader('foo'));
        // Retrieves a message header value
        $this->assertSame(['BAR'], $message->getHeader('foo'));
        // Retrieves a comma-separated string of the values for a single header
        $this->assertSame('BAR', $message->getHeaderLine('foo'));

        // "Rename" the header name by removing it and replacing it
        $message = $message->withoutHeader('foo')->withHeader('foo', 'BAR');
        $this->assertSame(['foo' => ['BAR']], $message->getHeaders());
    }

    // ------------------------------------------
    // Body
    // ------------------------------------------

    public function testGetBody()
    {
        $message = $this->getMessage();

        // Returns the body as a stream.
        $body = $message->getBody();
        $this->assertInstanceOf(StreamInterface::class, $body);
    }

    public function testWithBody()
    {
        $message = $this->getMessage();
        $mockStreamInterface = $this->getMockBuilder(StreamInterface::class)->getMock();

        // Returns the body as a stream.
        $message2 = $message->withbody($mockStreamInterface);
        $this->assertSame($mockStreamInterface, $message2->getBody());

        // Returns the body as a stream.
        $message3 = $message2->withbody($mockStreamInterface);
        $this->assertSame($message2, $message3);
    }
}
