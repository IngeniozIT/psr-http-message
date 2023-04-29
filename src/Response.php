<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{ResponseInterface, StreamInterface};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Response\ReasonPhrase;

readonly class Response extends Message implements ResponseInterface
{
    protected string $reasonPhrase;

    public function __construct(
        string $protocolVersion,
        Headers $headers,
        StreamInterface $body,
        protected int $statusCode,
        string $reasonPhrase,
    ) {
        parent::__construct($protocolVersion, $headers, $body);
        $this->reasonPhrase = $this->computeReasonPhrase($this->statusCode, $reasonPhrase);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $code === $this->statusCode && ($reasonPhrase === $this->reasonPhrase || $reasonPhrase === $this->getReasonPhrase()) ?
            $this :
            /* @phpstan-ignore-next-line */
            new static(...$this->newInstanceWithParams([
                'statusCode' => $code,
                'reasonPhrase' => $reasonPhrase,
            ]));
    }

    /**
     * @param array{protocolVersion?: string, headers?: ?Headers, body?: StreamInterface, statusCode?: int, reasonPhrase?: string} $params
     * @return array{protocolVersion: string, headers: Headers, body: StreamInterface, statusCode: int, reasonPhrase: string}
     */
    protected function newInstanceWithParams(array $params): array
    {
        return array_merge(
            parent::newInstanceWithParams($params),
            [
                'statusCode' => $params['statusCode'] ?? $this->statusCode,
                'reasonPhrase' => $params['reasonPhrase'] ?? $this->reasonPhrase,
            ],
        );
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function computeReasonPhrase(int $statusCode, string $reasonPhrase): string
    {
        return $reasonPhrase !== '' ?
            $reasonPhrase :
            ReasonPhrase::REASON_PHRASES[$statusCode] ?? '';
    }
}
