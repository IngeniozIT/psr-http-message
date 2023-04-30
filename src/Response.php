<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use Psr\Http\Message\{ResponseInterface, StreamInterface};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Response\ReasonPhrase;

readonly final class Response extends Message implements ResponseInterface
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

    /**
     * @return array{protocolVersion: string, headers: Headers, body: StreamInterface, statusCode: int, reasonPhrase: string}
     */
    protected function getConstructorParams(): array
    {
        return array_merge(
            parent::getConstructorParams(),
            [
                'statusCode' => $this->statusCode,
                'reasonPhrase' => $this->reasonPhrase,
            ],
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @phan-suppress PhanParamTooFewUnpack
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $code === $this->statusCode && ($reasonPhrase === $this->reasonPhrase || $reasonPhrase === $this->getReasonPhrase()) ?
            $this :
            new self(...array_merge(
                $this->getConstructorParams(),
                [
                    'statusCode' => $code,
                    'reasonPhrase' => $reasonPhrase,
                ],
            ));
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
