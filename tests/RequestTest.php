<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message\Tests;

use IngeniozIT\Http\Message\Tests\MessageTest;
use Psr\Http\Message\{RequestInterface, MessageInterface, UriInterface};

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\Request
 */
class RequestTest extends MessageTest
{
    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /**
     * Get a new RequestInterface instance.
     *
     * @param  array         (optional) $headers         (optional) HTTP headers.
     * @param  string       (optional)  $protocolVersion (optional) HTTP procol version.
     * @param  string       (optional)  $method          (optional) HTTP method.
     * @param  UriInterface (optional)  $uri             (optional) Uri.
     * @return RequestInterface
     */
    protected function getMessage(
        array $headers = [],
        ?string $protocolVersion = null,
        ?string $method = null,
        $uri = null
    ) {
        $mockStreamInterface = $this->getMockStream();

        if ($uri !== null) {
            return new \IngeniozIT\Http\Message\Request($mockStreamInterface, $headers, $protocolVersion, $method, $uri);
        } elseif ($method !== null) {
            return new \IngeniozIT\Http\Message\Request($mockStreamInterface, $headers, $protocolVersion, $method);
        } elseif ($protocolVersion !== null) {
            return new \IngeniozIT\Http\Message\Request($mockStreamInterface, $headers, $protocolVersion);
        } elseif ($headers !== []) {
            return new \IngeniozIT\Http\Message\Request($mockStreamInterface, $headers);
        }

        return new \IngeniozIT\Http\Message\Request($mockStreamInterface);
    }

    /**
     * Get a new RequestInterface instance.
     *
     * @param  string $method (optional) HTTP method.
     * @param  string $uri    (optional) Uri.
     * @param  string $host   (optional) Return of $uri->getHost().
     * @return RequestInterface
     */
    protected function getRequest($method = null, $uri = null, $host = null)
    {
        if ($uri !== null) {
            $mockUriInterface = $this->getMockUri();
            $mockUriInterface->method('__toString')->willReturn($uri);

            if ($host !== null) {
                $mockUriInterface->method('getHost')->willReturn($host);
            }

            $uri = $mockUriInterface;
        }

        return $this->getMessage([], null, $method, $uri);
    }

    /**
     * Get a \Psr\Http\Message\UriInterface mock.
     *
     * @return UriInterface
     */
    protected function getMockUri(): UriInterface
    {
        return $this->createMock(UriInterface::class);
    }

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getMessage() and getRequest() return a RequestInterface ?
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(RequestInterface::class, $this->getMessage(), 'getMessage does not give a RequestInterface object.');
        $this->assertInstanceOf(RequestInterface::class, $this->getRequest(), 'getRequest does not give a RequestInterface object.');
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
        $mockUriInterface = $this->getMockUri();
        $mockUriInterface->method('__toString')->willReturn('hostname/foo');
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
        /**
 * @var UriInterface $mockUriInterface
*/
        $mockUriInterface = $this->getMockUri();
        $mockUriInterface->method('__toString')->willReturn('/');
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
        /**
 * @var UriInterface $mockUriInterface
*/
        $mockUriInterface = $this->getMockUri();
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

    // ========================================== //
    // Method                                     //
    // ========================================== //

    /**
     * Retrieves the HTTP method of the request.
     *
     * @dataProvider getValidHttpMethodsProvider
     */
    public function testGetMethod($method)
    {
        $request = $this->getRequest();
        $request = $request->withMethod($method);
        $this->assertSame($method, $request->getMethod());
    }

    /**
     * Return an instance with the provided HTTP method.
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * @dataProvider getValidHttpMethodsProvider
     */
    public function testWithMethodCaseSensitive($method)
    {
        $method = strtolower($method);
        $request = $this->getRequest();

        $this->expectException(\InvalidArgumentException::class);
        $request->withMethod($method);
    }

    /**
     * Provider. Gives valid methods.
     */
    public function getValidHttpMethodsProvider(): array
    {
        $validMethods = [
            'GET',
            'HEAD',
            'POST',
            'PUT',
            'DELETE',
            'CONNECT',
            'OPTIONS',
            'TRACE',
        ];

        $ret = [];
        foreach ($validMethods as $validMethod) {
            $ret[$validMethod] = [$validMethod];
        }
        return $ret;
    }

    /**
     * Return an instance with the provided HTTP method.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     */
    public function testWithMethodImmutability()
    {
        $request = $this->getRequest('GET');
        $request2 = $request->withMethod('GET');
        $request3 = $request->withMethod('POST');

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('GET', $request2->getMethod());
        $this->assertSame('POST', $request3->getMethod());

        $this->assertNotSame($request3, $request, 'Method is not immutable.');
        $this->assertSame($request, $request2, 'Method is badly immutable.');
    }

    // ========================================== //
    // Uri                                        //
    // ========================================== //

    /**
     * Retrieves the URI instance.
     * This method MUST return a UriInterface instance.
     */
    public function testGetUriGivesUriInterface()
    {
        $request = $this->getRequest();

        $this->assertInstanceOf(UriInterface::class, $request->getUri());
    }

    /**
     * Returns an instance with the provided URI.
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component.
     *
     * If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     */
    public function testWithUriDefault()
    {
        $request = $this->getRequest();

        $mockUriInterface = $this->getMockUri();
        $mockUriInterface->method('__toString')->willReturn('hostname/foo');
        $mockUriInterface->method('getHost')->willReturn('hostname');

        $request = $request->withUri($mockUriInterface);

        $this->assertSame('hostname', $request->getUri()->getHost());
        $this->assertSame('hostname', $request->getHeaderLine('Host'));

        $mockUriInterface3 = $this->getMockUri();
        $mockUriInterface3->method('__toString')->willReturn('hostname/bar');
        $mockUriInterface3->method('getHost')->willReturn('hostname');

        $mockUriInterface2 = $this->getMockUri();
        $mockUriInterface2->method('__toString')->willReturn('/bar');
        $mockUriInterface2->method('getHost')->willReturn('');
        $mockUriInterface2->method('withHost')->willReturn($mockUriInterface3);

        $request2 = $request->withUri($mockUriInterface2);

        $this->assertSame('hostname', $request2->getUri()->getHost());
        $this->assertSame('hostname', $request2->getHeaderLine('Host'));
        $this->assertSame('hostname/bar', $request2->getRequestTarget());
    }

    /**
     * Returns an instance with the provided URI.
     * When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     */
    public function testWithUriPreserveHostCase1()
    {
        // Setup
        $request = $this->getRequest();

        $mockUriInterface = $this->getMockUri();
        $mockUriInterface->method('__toString')->willReturn('/foo');
        $mockUriInterface->method('getHost')->willReturn('');

        $request = $request->withUri($mockUriInterface);

        $this->assertSame('', $request->getUri()->getHost());
        $this->assertSame('', $request->getHeaderLine('Host'));

        $mockUriInterface2 = $this->getMockUri();
        $mockUriInterface2->method('__toString')->willReturn('hostname/foo');
        $mockUriInterface2->method('getHost')->willReturn('hostname');

        // Test
        $request2 = $request->withUri($mockUriInterface2, true);

        $this->assertSame('hostname', $request2->getUri()->getHost());
        $this->assertSame('hostname', $request2->getHeaderLine('Host'));
        $this->assertSame('hostname/foo', $request2->getRequestTarget());
    }

    /**
     * Returns an instance with the provided URI.
     * When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     */
    public function testWithUriPreserveHostCase2()
    {
        // Setup
        $request = $this->getRequest();

        $mockUriInterface = $this->getMockUri();
        $mockUriInterface->method('__toString')->willReturn('/foo');
        $mockUriInterface->method('getHost')->willReturn('');

        $request = $request->withUri($mockUriInterface);

        $mockUriInterface2 = $this->getMockUri();
        $mockUriInterface2->method('__toString')->willReturn('/foo');
        $mockUriInterface2->method('getHost')->willReturn('');

        // Test
        $request2 = $request
            ->withHeader('Host', 'hostname')
            ->withUri($mockUriInterface2, true);

        $this->assertSame('', $request2->getUri()->getHost());
        $this->assertSame('hostname', $request2->getHeaderLine('Host'));
        $this->assertSame('/foo', $request2->getRequestTarget());
    }

    /**
     * Returns an instance with the provided URI.
     * When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     */
    public function testWithUriPreserveHostCase3()
    {
        // Setup
        $request = $this->getRequest()->withHeader('Host', 'hostname');

        $mockUriInterface = $this->getMockUri();
        $mockUriInterface->method('__toString')->willReturn('badhostname/foo');
        $mockUriInterface->method('getHost')->willReturn('badhostname');

        // Test
        $request2 = $request->withUri($mockUriInterface, true);

        $this->assertSame('hostname', $request2->getHeaderLine('Host'));
    }
}
