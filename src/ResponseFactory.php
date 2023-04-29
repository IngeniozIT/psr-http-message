<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{ResponseFactoryInterface, StreamFactoryInterface, ResponseInterface};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;

readonly final class ResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        private StreamFactoryInterface $streamFactory
    ) {
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response(
            '1.1',
            new Headers([]),
            $this->streamFactory->createStream(),
            $code,
            $reasonPhrase,
        );
    }
}
