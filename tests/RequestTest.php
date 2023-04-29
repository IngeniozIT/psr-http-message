<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use Psr\Http\Message\{RequestInterface, UriInterface};
use IngeniozIT\Http\Message\{StreamFactory,
    UriFactory,
    Request,
};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Request\Method;

class RequestTest extends MessageTest
{
    protected function getMessage(): RequestInterface
    {
        $streamFactory = new StreamFactory();
        $uriFactory = new UriFactory();
        return new Request(
            '1.1',
            new Headers([]),
            $streamFactory->createStream(),
            Method::GET,
            '',
            $uriFactory->createUri(),
        );
    }

    public function testIsAPsrRequest(): void
    {
        $request = $this->getMessage();

        self::assertInstanceOf(RequestInterface::class, $request);
    }

    /**
     * @dataProvider providerRequestTargets
     */
    public function testHasARequestTarget(
        string $requestTarget,
        UriInterface $uri,
        string $expected
    ): void {
        $request = $this->getMessage()
            ->withRequestTarget($requestTarget)
            ->withUri($uri);
        $requestTarget = $request->getRequestTarget();

        self::assertEquals($expected, $requestTarget);
    }

    /**
     * @return array<string, array{requestTarget: string, uri: UriInterface, expected: string}>
     */
    public static function providerRequestTargets(): array
    {
        $uriFactory = new UriFactory();
        return [
            'from request target' => [
                'requestTarget' => '/foo/bar',
                'uri' => $uriFactory->createUri(),
                'expected' => '/foo/bar',
            ],
            'from uri' => [
                'requestTarget' => '',
                'uri' => $uriFactory->createUri('/foo/bar'),
                'expected' => '/foo/bar',
            ],
            'request target takes precedence over uri' => [
                'requestTarget' => '/foo/bar',
                'uri' => $uriFactory->createUri('/not/foo/bar'),
                'expected' => '/foo/bar',
            ],
            'empty request target and uri return /' => [
                'requestTarget' => '',
                'uri' => $uriFactory->createUri(),
                'expected' => '/',
            ],
            'multiple leading / are removed from request target' => [
                'requestTarget' => '//foo/bar',
                'uri' => $uriFactory->createUri(),
                'expected' => '/foo/bar',
            ],
            'multiple leading / are removed from uri' => [
                'requestTarget' => '',
                'uri' => $uriFactory->createUri('http://example.com///foo'),
                'expected' => '/foo',
            ],
        ];
    }

    /**
     * @dataProvider providerMethods
     */
    public function testHasAMethod(string $method): void
    {
        $request = $this->getMessage()->withMethod($method);
        $computedMethod = $request->getMethod();

        self::assertEquals($method, $computedMethod);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function providerMethods(): array
    {
        return [
            'GET' => ['GET'],
            'POST' => ['POST'],
            'PUT' => ['PUT'],
            'PATCH' => ['PATCH'],
            'DELETE' => ['DELETE'],
            'HEAD' => ['HEAD'],
            'OPTIONS' => ['OPTIONS'],
            'TRACE' => ['TRACE'],
            'CONNECT' => ['CONNECT'],
        ];
    }

    public function testHasAUri(): void
    {
        $uri = (new UriFactory())->createUri('http://example.com/foo/bar');
        $request = $this->getMessage()->withUri($uri);
        $uriString = $request->getUri();

        self::assertEquals('http://example.com/foo/bar', $uriString);
    }

    /**
     * @dataProvider providerHostHeaderModifiers
     */
    public function testCanUpdateHostHeaderWhenGivenAUri(
        string $initialHost,
        UriInterface $uri,
        ?bool $preserveHost,
        string $expectedHost,
    ): void {
        $request = $this->getMessage();
        if (!empty($initialHost)) {
            $request = $request->withHeader('Host', $initialHost);
        }

        $request = $preserveHost !== null ?
            $request->withUri($uri, $preserveHost) :
            $request->withUri($uri);
        $host = $request->getHeaderLine('Host');

        self::assertEquals($expectedHost, $host);
    }

    /**
     * @return array<string, array{initialHost: string, uri: UriInterface, preserveHost: ?bool, expectedHost: string}>
     */
    public static function providerHostHeaderModifiers(): array
    {
        $uriFactory = new UriFactory();
        return [
            'no preserve host' => [
                'initialHost' => 'foo.com',
                'uri' => $uriFactory->createUri('http://example.com/foo/bar'),
                'preserveHost' => false,
                'expectedHost' => 'example.com',
            ],
            'no initial host + no preserve host' => [
                'initialHost' => '',
                'uri' => $uriFactory->createUri('http://example.com/foo/bar'),
                'preserveHost' => false,
                'expectedHost' => 'example.com',
            ],
            'no initial host + preserve host' => [
                'initialHost' => '',
                'uri' => $uriFactory->createUri('http://example.com/foo/bar'),
                'preserveHost' => true,
                'expectedHost' => 'example.com',
            ],
            'preserve host' => [
                'initialHost' => 'foo.com',
                'uri' => $uriFactory->createUri('http://example.com/foo/bar'),
                'preserveHost' => true,
                'expectedHost' => 'foo.com',
            ],
            'no uri host' => [
                'initialHost' => 'foo.com',
                'uri' => $uriFactory->createUri('/foo/bar'),
                'preserveHost' => false,
                'expectedHost' => 'foo.com',
            ],
            'default preserve host is null' => [
                'initialHost' => 'foo.com',
                'uri' => $uriFactory->createUri('http://example.com/foo/bar'),
                'preserveHost' => null,
                'expectedHost' => 'example.com',
            ],
        ];
    }

    public function testAddsHostHeaderWhenUriWithHostIsProvided(): void
    {
        $streamFactory = new StreamFactory();
        $uri = (new UriFactory())->createUri('http://example.com/');

        $request = new Request(
            '1.1',
            new Headers([]),
            $streamFactory->createStream(),
            Method::GET,
            '',
            $uri,
        );
        $host = $request->getHeaderLine('Host');

        self::assertEquals('example.com', $host);
    }

    public function testUsesTheSameInstanceWhenContentDoesNotChange(): void
    {
        $stream = (new StreamFactory())->createStream('test');
        $uri = (new UriFactory())->createUri('http://example.com/foo/bar');
        $request = $this->getMessage()
            ->withProtocolVersion('2.0')
            ->withHeader('X-Test', 'test')
            ->withBody($stream)
            ->withMethod('PUT')
            ->withRequestTarget('/foo/bar')
            ->withUri($uri);

        $request2 = $request
            ->withProtocolVersion('2.0')
            ->withHeader('X-Test', 'test')
            ->withAddedHeader('X-Test', 'test')
            ->withoutHeader('X-Test2')
            ->withBody($stream)
            ->withMethod('PUT')
            ->withRequestTarget('/foo/bar')
            ->withUri($uri);

        self::assertSame($request, $request2);
    }
}
