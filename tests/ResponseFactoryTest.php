<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Http\Message\{ResponseFactory, StreamFactory};
use Psr\Http\Message\ResponseFactoryInterface;

class ResponseFactoryTest extends TestCase
{
    public function testIsAPsrResponseFactory(): void
    {
        $streamFactory = new ResponseFactory(new StreamFactory());

        self::assertInstanceOf(ResponseFactoryInterface::class, $streamFactory);
    }

    public function testCanCreateAResponse(): void
    {
        $streamFactory = new ResponseFactory(new StreamFactory());

        $response = $streamFactory->createResponse();
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        self::assertEquals(200, $statusCode);
        self::assertEquals('OK', $reasonPhrase);
    }

    public function testCanCreateAResponseWithCustomCodeAndReasonPhrase(): void
    {
        $streamFactory = new ResponseFactory(new StreamFactory());

        $response = $streamFactory->createResponse(404, 'Custom Reason Phrase');
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        self::assertEquals(404, $statusCode);
        self::assertEquals('Custom Reason Phrase', $reasonPhrase);
    }
}
