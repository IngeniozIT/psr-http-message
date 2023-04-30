<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use Psr\Http\Message\ResponseInterface;
use IngeniozIT\Http\Message\{StreamFactory, Response};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;

class ResponseTest extends MessageTest
{
    protected function getMessage(): ResponseInterface
    {
        $streamFactory = new StreamFactory();
        return new Response(
            '1.1',
            new Headers([]),
            $streamFactory->createStream(),
            200,
            ''
        );
    }

    public function testIsAPsrResponse(): void
    {
        $response = $this->getMessage();

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @dataProvider providerStatusCodes
     */
    public function testHasAStatusCodeAndAReasonPhrase(int $statusCode, string $expctedReasonPhrase): void
    {
        $response = $this->getMessage()
            ->withStatus($statusCode);
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        self::assertEquals($statusCode, $statusCode);
        self::assertEquals($expctedReasonPhrase, $reasonPhrase);
    }

    /**
     * @return array<string, array{statusCode: int, reasonPhrase: string}>
     */
    public static function providerStatusCodes(): array
    {
        return [
            'HTTP 200' => [
                'statusCode' => 200,
                'reasonPhrase' => 'OK',
            ],
            'HTTP 404' => [
                'statusCode' => 404,
                'reasonPhrase' => 'Not Found',
            ],
            'HTTP 500' => [
                'statusCode' => 500,
                'reasonPhrase' => 'Internal Server Error',
            ],
        ];
    }

    public function testCanUseCustomReasonPhrase(): void
    {
        $response = $this->getMessage()
            ->withStatus(200, 'Custom Reason Phrase');
        $reasonPhrase = $response->getReasonPhrase();

        self::assertEquals('Custom Reason Phrase', $reasonPhrase);
    }

    public function testUsesTheSameInstanceWhenItsContentDoesNotChange(): void
    {
        $stream = (new StreamFactory())->createStream('test');
        $response = $this->getMessage()
            ->withProtocolVersion('2.0')
            ->withHeader('X-Test', 'test')
            ->withBody($stream)
            ->withStatus(500, 'Not OK');

        $response2 = $response
            ->withProtocolVersion('2.0')
            ->withHeader('X-Test', 'test')
            ->withAddedHeader('X-Test', 'test')
            ->withoutHeader('X-Test2')
            ->withBody($stream)
            ->withStatus(500, 'Not OK');

        self::assertSame($response, $response2);
    }
}
