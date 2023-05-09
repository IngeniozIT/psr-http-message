<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message;

use IngeniozIT\Http\Message\ValueObject\Message\Header;
use Psr\Http\Message\{
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UploadedFileInterface,
    UriFactoryInterface,
    UriInterface,
    ServerRequestInterface
};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Request\Method;
use InvalidArgumentException;

readonly final class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function __construct(
        private StreamFactoryInterface $streamFactory,
        private UriFactoryInterface $uriFactory,
        private UploadedFileFactoryInterface $uploadedFileFactory,
    ) {
    }

    /**
     * @param UriInterface|string $uri
     * @param array<string, mixed> $serverParams
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest(
            protocolVersion: '',
            headers: new Headers([]),
            body: $this->streamFactory->createStreamFromFile('php://input'),
            method: Method::tryFrom($method) ?? throw new InvalidArgumentException("Invalid method: $method"),
            requestTarget: '',
            uri: is_string($uri) ? $this->uriFactory->createUri($uri) : $uri,
            serverParams: $serverParams,
            cookieParams: [],
            queryParams: [],
            uploadedFiles: [],
            parsedBody: null,
            attributes: [],
        );
    }

    /**
     * @param array<string, mixed> $globals
     */
    public function createServerRequestFromGlobals(array $globals): ServerRequestInterface
    {
        /** @var array<string, mixed> $uploadedFiles */
        $uploadedFiles = $this->extractUploadedFiles($globals['_FILES'] ?? []);
        return new ServerRequest(
            protocolVersion: $this->extractProtocolVersion($globals['_SERVER'] ?? []),
            headers: new Headers($this->extractHeaders($globals['_SERVER'] ?? [])),
            body: $this->streamFactory->createStreamFromFile('php://input'),
            method: $this->extractMethod($globals['_SERVER'] ?? []),
            requestTarget: $this->extractRequestTarget($globals['_SERVER'] ?? []),
            uri: $this->extractUri($globals['_SERVER'] ?? []),
            serverParams: $globals['_SERVER'] ?? [],
            cookieParams: $this->extractCookies($globals['_COOKIE'] ?? [], $globals['_SERVER'] ?? []),
            queryParams: $globals['_GET'] ?? [],
            uploadedFiles: $uploadedFiles,
            parsedBody: $globals['_POST'] ?? null,
            attributes: [],
        );
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    private function extractProtocolVersion(array $serverParams): string
    {
        $serverProtocol = $serverParams['SERVER_PROTOCOL'] ?? '';
        return explode('/', $serverProtocol)[1] ?? '1.1';
    }

    /**
     * @param array<string, mixed> $serverParams
     * @return Header[]
     */
    private function extractHeaders(array $serverParams): array
    {
        $headers = [];
        foreach ($serverParams as $key => $value) {
            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }
            $headerName = str_replace('_', '-', substr($key, 5));
            $headers[$headerName] = new Header($headerName, $this->formatHeaderValue($value));
        }
        return $headers;
    }

    /**
     * @return string[]
     */
    private function formatHeaderValue(string $value): array
    {
        return array_map('trim', explode(',', $value));
    }

    /**
     * @param array<string, mixed> $serverParams
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function extractMethod(array $serverParams): Method
    {
        $method = $serverParams['REQUEST_METHOD'] ?? 'GET';
        return Method::tryFrom($method) ?? throw new InvalidArgumentException("Invalid method: $method");
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    private function extractRequestTarget(array $serverParams): string
    {
        return $serverParams['REQUEST_URI'] ?? '';
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    private function extractUri(array $serverParams): UriInterface
    {
        return $this->uriFactory->createUri(
            ($serverParams['HTTP_HOST'] ?? '') .
            ($serverParams['REQUEST_URI'] ?? '')
        );
    }

    /**
     * @param array<string, string> $cookies
     * @param array<string, mixed> $serverParams
     * @return array<string, string>
     */
    private function extractCookies(array $cookies, array $serverParams): array
    {
        $headerCookies = array_filter(explode(';', $serverParams['HTTP_COOKIE'] ?? ''));
        foreach ($headerCookies as $headerCookie) {
            $cookieData = explode('=', $headerCookie, 2);
            $cookies[trim($cookieData[0])] = trim($cookieData[1] ?? '');
        }
        return $cookies;
    }

    /**
     * @param array<string, mixed> $files
     * @return array<string, mixed>|UploadedFileInterface
     */
    private function extractUploadedFiles(array $files): UploadedFileInterface|array
    {
        if (array_key_exists('tmp_name', $files)) {
            $files = !is_array($files['tmp_name']) ?
                $this->uploadedFileFactory->createUploadedFile(
                    $this->streamFactory->createStreamFromFile($files['tmp_name']),
                    $files['size'] ?? null,
                    $files['error'] ?? UPLOAD_ERR_OK,
                    $files['name'] ?? null,
                    $files['type'] ?? null,
                ) :
                $this->flipFileArray($files);
        }

        return $files instanceof UploadedFileInterface ?
            $files :
            array_map($this->extractUploadedFiles(...), $files);
    }

    /**
     * @param array<string, mixed[]> $files
     * @return mixed[]
     */
    private function flipFileArray(array $files): array
    {
        foreach (array_keys($files['tmp_name']) as $index) {
            foreach (['tmp_name', 'size', 'error', 'name', 'type'] as $key) {
                $files[$index][$key] = $files[$key][$index];
                unset($files[$key][$index]);
            }
        }
        return $files;
    }
}
