<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message\Tests;

use IngeniozIT\Http\Message\Tests\MessageTest;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\Request
 */
class RequestTest extends MessageTest
{
    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /**
     * Get a new MessageInterface instance.
     *
     * @return MessageInterface
     */
    protected function getMessage($headers = [], $protocolVersion = null, $method = 'GET', $uri = null)
    {
        /** @var StreamInterface $mockStreamInterface */
        $mockStreamInterface = $this->createMock(StreamInterface::class);
        return new \IngeniozIT\Http\Message\Request($mockStreamInterface, $headers, $protocolVersion, $method, $uri);
    }

    protected function getRequest($method = 'GET', $uri = null)
    {
        if ($uri !== null) {
            $mockUriInterface = $this->createMock(UriInterface::class);
            $mockUriInterface->method('__toString')->willReturn($uri);

            $uri = $mockUriInterface;
        }
        return $this->getMessage([], null, $method, $uri);
    }

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getMessage() and getRequest() return a RequestInterface ?
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(RequestInterface::class, $this->getMessage(), 'Constructor does not give a RequestInterface object.');
        $this->assertInstanceOf(RequestInterface::class, $this->getRequest(), 'Constructor does not give a RequestInterface object.');
    }

    /**
     * During construction, implementations MUST attempt to set the Host header from
     * a provided URI if no Host header is provided.
     * Set the host with headers.
     */
    public function testConstructSetHostHeader()
    {
        $request = $this->getMessage(['Host' => 'hostname'], null, 'GET', null);

        $this->assertSame('hostname', $request->getHeaderLine('Host'));
    }

    /**
     * During construction, implementations MUST attempt to set the Host header from
     * a provided URI if no Host header is provided.
     * Set the host with a given Uri.
     */
    public function testConstructSetHostHeaderWithUri()
    {
        $mockUriInterface = $this->createMock(UriInterface::class);
        $mockUriInterface->method('__toString')->willReturn('hostname');
        $mockUriInterface->method('getHost')->willReturn('hostname');

        $request = $this->getMessage([], null, 'GET', $mockUriInterface);

        $this->assertSame('hostname', $request->getHeaderLine('Host'));
    }

    /**
     * During construction, implementations MUST attempt to set the Host header from
     * a provided URI if no Host header is provided.
     * Give an Uri without host. Expect no host header.
     */
    public function testConstructSetHostHeaderWithUriWithNoHost()
    {
        $mockUriInterface = $this->createMock(UriInterface::class);
        $mockUriInterface->method('__toString')->willReturn('');
        $mockUriInterface->method('getHost')->willReturn('');

        $request = $this->getMessage([], null, 'GET', $mockUriInterface);

        $this->assertFalse($request->hasHeader('Host'));
    }

    /**
     * During construction, implementations MUST attempt to set the Host header from
     * a provided URI if no Host header is provided.
     * Set the host with headers, give a Uri with a host. Expect the Uri does not
     * override host.
     */
    public function testConstructSetHostHeaderAndGiveUri()
    {
        $mockUriInterface = $this->createMock(UriInterface::class);
        $mockUriInterface->method('__toString')->willReturn('badhostname');
        $mockUriInterface->method('getHost')->willReturn('badhostname');

        $request = $this->getMessage(['Host' => 'hostname'], null, 'GET', $mockUriInterface);

        $this->assertSame('hostname', $request->getHeaderLine('Host'));
    }

    // ========================================== //
    // Request Target                             //
    // ========================================== //

    /**
     * Retrieves the message's request target.
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     */
    public function testGetRequestTargetDefaultValue()
    {
        $request = $this->getRequest();

        $this->assertSame('/', $request->getRequestTarget());
    }

    /**
     * Retrieves the message's request target.
     * In most cases, this will be the origin-form of the composed URI
     */
    public function testGetRequestTargetWithUri()
    {
        $uri = 'http://example.com/path?query=yes#fragment';

        $request = $this->getRequest('GET', $uri);

        $this->assertSame($uri, $request->getRequestTarget());
    }

    /**
     * Retrieves the message's request target.
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     */
    public function testGetRequestTargetWithRequestTarget()
    {
        $uri = 'http://example.com/path?query=yes#fragment';

        $request = $this->getRequest()->withRequestTarget($uri);

        $this->assertSame($uri, $request->getRequestTarget());
    }

    /**
     * Return an instance with the specific request-target.
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * @dataProvider getRequestTargetFormsProvider
     */
    public function testWithRequestTargetForm(string $uri)
    {
        $uri = 'http://example.com/path?query=yes#fragment';

        $request = $this->getRequest()->withRequestTarget($uri);

        $this->assertSame($uri, $request->getRequestTarget());
    }

    /**
     * Provider. Gives request targets of each form.
     */
    public function getRequestTargetFormsProvider(): array
    {
        return [
            'origin-form' => ['/where?q=now'],
            'absolute-form' => ['http://www.example.org/pub/WWW/TheProject.html'],
            'authority-form' => ['www.example.com:80'],
            'asterisk-form' => ['*'],
        ];
    }

    /**
     * Return an instance with the specific request-target.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     */
    public function testWithRequestTargetImmutability()
    {
        $request = $this->getRequest();
        $request2 = $request->withRequestTarget('/');
        $request3 = $request->withRequestTarget('/foo');

        $this->assertSame('/', $request->getRequestTarget());
        $this->assertSame('/', $request2->getRequestTarget());
        $this->assertSame('/foo', $request3->getRequestTarget());

        $this->assertNotSame($request3, $request, 'Request target is not immutable.');
        $this->assertSame($request, $request2, 'Request target is badly immutable.');
    }


}
