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
     *
     * @var string
     */
    protected $defaultProtocolVersion = '1.1';

    /**
     * Get a new MessageInterface instance.
     *
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

    // ========================================== //
    // Protocol version                           //
    // ========================================== //

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * @dataProvider getProcotolVersionProvider
     * @param        mixed  $newProtocol      Protocol to give to the Message. null to
     *                                        disable giving a new protocol version.
     * @param        string $expectedProtocol Expected protocol value.
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
    public function getProcotolVersionProvider(): array
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
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @dataProvider getProcotolVersionInvalidVersionProvider
     * @param        mixed $newProtocol Protocol to give to the Message.
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
    public function getProcotolVersionInvalidVersionProvider(): array
    {
        return [
            'full http version HTTP/2.0' => ['HTTP/2.0'],
            '(string)weird value' => ['test'],
            '(bool)weird string' => [false],
            '(array)weird string' => [[]],
            '(array2)weird string' => [['test']],
        ];
    }

    // ========================================== //
    // Headers                                    //
    // ========================================== //

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @dataProvider getValidHeadersProvider
     * @param        array $headers         The headers to check.
     * @param        array $expectedHeaders The expected output of getHeaders().
     */
    public function testGetHeaders(array $headers, array $expectedHeaders)
    {
        $message = $this->getMessage();

        foreach ($headers as $name => $value) {
            $message = $message->withAddedHeader($name, $value);
        }

        $this->assertSame($expectedHeaders, $message->getHeaders());
    }

    /**
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * @dataProvider getValidHeadersProvider
     * @param        array $headers         The headers to check.
     * @param        array $expectedHeaders The expected output of getHeaders().
     */
    public function testGetHeader(array $headers, array $expectedHeaders)
    {
        $message = $this->getMessage();

        foreach ($headers as $name => $value) {
            $message = $message->withAddedHeader($name, $value);
        }

        foreach ($expectedHeaders as $name => $expectedHeader) {
            $this->assertSame($expectedHeader, $message->getHeader($name));
        }

        if (empty($expectedHeaders)) {
            $this->assertSame([], $message->getHeader('aNonExistingHeader'));
        }
    }

    /**
     * Provider. Return valid headers.
     */
    public function getValidHeadersProvider(): array
    {
        return [
            'No headers' => [
                [],
                [],
            ],
            'One header' => [
                [
                    'foo' => ['bar'],
                ],
                [
                    'foo' => ['bar'],
                ],
            ],
            'Multiple headers' => [
                [
                    'foo' => ['bar'],
                    'bar' => 'baz,foo',
                    'baz' => ['foo', 'bar', 'baz'],
                ],
                [
                    'foo' => ['bar'],
                    'bar' => ['baz,foo'],
                    'baz' => ['foo', 'bar', 'baz'],
                ],
            ],
            'Case insensitive headers' => [
                [
                    'FoO' => ['bar'],
                    'FOO' => ['baz', 'foo'],
                    'foo' => ['foo', 'bar', 'baz'],
                ],
                [
                    'foo' => ['bar', 'baz', 'foo', 'foo', 'bar', 'baz'],
                ],
            ],
        ];
    }

    /**
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @dataProvider getValidHeaderLinesProvider
     * @param        array $headers             The headers to add.
     * @param        array $expectedHeaderLines The expected output of getHeaderLine().
     */
    public function testGetHeaderLine(array $headers, array $expectedHeaderLines)
    {
        $message = $this->getMessage();

        foreach ($headers as $name => $value) {
            $message = $message->withAddedHeader($name, $value);
        }

        foreach ($expectedHeaderLines as $name => $expectedHeaderLine) {
            $this->assertSame($expectedHeaderLine, $message->getHeaderLine($name));
        }
    }

    /**
     * Provider. Return valid headers.
     */
    public function getValidHeaderLinesProvider(): array
    {
        return [
            'No headers' => [
                [],
                [
                    'foo' => ''
                ],
            ],
            'One header' => [
                [
                    'foo' => ['bar'],
                ],
                [
                    'foo' => 'bar',
                    'bar' => '',
                ],
            ],
            'Multiple headers' => [
                [
                    'foo' => ['bar'],
                    'bar' => 'baz,foo',
                    'baz' => ['foo', 'bar', 'baz'],
                ],
                [
                    'foo' => 'bar',
                    'bar' => 'baz,foo',
                    'baz' => 'foo,bar,baz',
                ],
            ],
            'Case insensitive headers' => [
                [
                    'FoO' => ['bar'],
                    'FOO' => ['baz', 'foo'],
                    'foo' => ['foo', 'bar', 'baz'],
                ],
                [
                    'foo' => 'bar,baz,foo,foo,bar,baz',
                ],
            ],
        ];
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     */
    public function testHasHeader()
    {
        $message = $this->getMessage();

        $this->assertFalse($message->hasHeader('foo'));

        $message = $message->withHeader('foo', 'bar');
        $this->assertTrue($message->hasHeader('foo'));
        $this->assertTrue($message->hasHeader('FOO'));
    }

    /**
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     */
    public function testWithHeaderReturnsNewInstance()
    {
        $message = $this->getMessage();

        $message2 = $message->withHeader('name', 'value');

        $this->assertFalse($message->hasHeader('name'));
        $this->assertSame('value', $message2->getHeaderLine('name'));
    }

    /**
     * If the header given is the same as the Message's header, the same
     * instance will be returned.
     */
    public function testWithHeaderReturnsSameInstanceOnSameValue()
    {
        $message = $this->getMessage()->withHeader('name', 'value');
        $message2 = $message->withHeader('name', 'value');

        $this->assertSame('value', $message->getHeaderLine('name'));
        $this->assertSame($message, $message2);
    }

    /**
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     */
    public function testWithoutHeaderReturnsNewInstance()
    {
        $message = $this->getMessage()->withHeader('name', 'value');

        $message2 = $message->withoutHeader('name');

        $this->assertTrue($message->hasHeader('name'));
        $this->assertFalse($message2->hasHeader('name'));
        $this->assertNotSame($message, $message2);
    }

    /**
     * If the header is not in the Message, the same instance will be returned.
     */
    public function testWithoutHeaderReturnsSameInstanceOnSameValue()
    {
        $message = $this->getMessage();
        $message2 = $message->withoutHeader('name');

        $this->assertFalse($message->hasHeader('name'));
        $this->assertSame($message, $message2);
    }

    /**
     * headers classic usage.
     *
     * @dataProvider getHeadersValuesProvider
     * @param        mixed $newValue      Header to give to the Message.
     * @param        array $expectedValue Expected header value.
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
     *
     * @dataProvider getHeadersInvalidValuesProvider
     * @param        mixed $value Value to give to the header.
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
     *
     * @dataProvider getHeadersInvalidNamesProvider
     * @param        mixed $name Name to give to the header.
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

    // ========================================== //
    // Body                                       //
    // ========================================== //

    /**
     * The body MUST be a StreamInterface object.
     */
    public function testGetBody()
    {
        $message = $this->getMessage();

        $body = $message->getBody();
        $this->assertInstanceOf(StreamInterface::class, $body);
    }

    /**
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     */
    public function testWithBody()
    {
        $message = $this->getMessage();
        $mockStreamInterface = $this->getMockBuilder(StreamInterface::class)->getMock();

        $message2 = $message->withbody($mockStreamInterface);
        $this->assertSame($mockStreamInterface, $message2->getBody());

        $message3 = $message2->withbody($mockStreamInterface);
        $this->assertSame($message2, $message3);
    }
}
