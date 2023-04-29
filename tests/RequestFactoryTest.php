<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Http\Message\{RequestFactory, StreamFactory, UriFactory};
use Psr\Http\Message\RequestFactoryInterface;
use InvalidArgumentException;

class RequestFactoryTest extends TestCase
{
    use WithTempFiles;

    public function testIsAPsrRequestFactory(): void
    {
        $streamFactory = new RequestFactory(new StreamFactory(), new UriFactory());

        self::assertInstanceOf(RequestFactoryInterface::class, $streamFactory);
    }

    public function testCanCreateARequestFromAUri(): void
    {
        $streamFactory = new RequestFactory(new StreamFactory(), new UriFactory());
        $uri = (new UriFactory())->createUri('http://example.com');

        $request = $streamFactory->createRequest('POST', $uri);

        self::assertEquals('http://example.com', $request->getUri());
        self::assertEquals('POST', $request->getMethod());
    }

    public function testCanCreateARequestFromAString(): void
    {
        $streamFactory = new RequestFactory(new StreamFactory(), new UriFactory());

        $request = $streamFactory->createRequest('PUT', 'http://example2.com');

        self::assertEquals('http://example2.com', $request->getUri());
        self::assertEquals('PUT', $request->getMethod());
    }

    public function testCannotCreateARequestWithAnInvalidMethod(): void
    {
        $streamFactory = new RequestFactory(new StreamFactory(), new UriFactory());

        self::expectException(InvalidArgumentException::class);
        $streamFactory->createRequest('INVALID_METHOD', 'http://example2.com');
    }
}
