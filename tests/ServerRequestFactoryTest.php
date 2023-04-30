<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use IngeniozIT\Http\Message\{ServerRequestFactory, StreamFactory, UriFactory};
use InvalidArgumentException;

class ServerRequestFactoryTest extends TestCase
{
    use WithTempFiles;

    public function testIsAPsrRequestFactory(): void
    {
        $streamFactory = new ServerRequestFactory(new StreamFactory(), new UriFactory());

        self::assertInstanceOf(ServerRequestFactoryInterface::class, $streamFactory);
    }

    public function testCanCreateARequestFromAUri(): void
    {
        $streamFactory = new ServerRequestFactory(new StreamFactory(), new UriFactory());
        $uri = (new UriFactory())->createUri('http://example.com');

        $request = $streamFactory->createServerRequest('POST', $uri, ['foo' => 'bar']);

        self::assertEquals('http://example.com', $request->getUri());
        self::assertEquals(['foo' => 'bar'], $request->getServerParams());
        self::assertEquals('POST', $request->getMethod());
    }

    public function testCanCreateARequestFromAStringUri(): void
    {
        $streamFactory = new ServerRequestFactory(new StreamFactory(), new UriFactory());

        $request = $streamFactory->createServerRequest('PUT', 'http://example2.com');

        self::assertEquals('http://example2.com', $request->getUri());
        self::assertEquals('PUT', $request->getMethod());
    }

    public function testCannotCreateARequestWithAnInvalidMethod(): void
    {
        $streamFactory = new ServerRequestFactory(new StreamFactory(), new UriFactory());

        self::expectException(InvalidArgumentException::class);
        $streamFactory->createServerRequest('INVALID_METHOD', 'http://example2.com');
    }
}
